<?php

/**
 * Controller for all teacher-related views.
 *
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Add a session (confirmed action) from data entered into the add session form
 * @param bookking_instance $bookking
 * @param mixed $formdata
 * @param moodle_url $returnurl the URL to redirect to after the action has been performed
 */
function bookking_action_doaddsession($bookking, $formdata, moodle_url $returnurl) {

    global $DB, $output;

    $data = (object) $formdata;

    $fordays = 0;
    if ($data->rangeend > 0) {
        $fordays = ($data->rangeend - $data->rangestart) / DAYSECS;
    }

    // Create as many slots of $duration as will fit between $starttime and $endtime and that do not conflict.
    $countslots = 0;
    $couldnotcreateslots = '';
    $startfrom = $data->rangestart + ($data->starthour * 60 + $data->startminute) * 60;
    $endat = $data->rangestart + ($data->endhour * 60 + $data->endminute) * 60;
    $slot = new stdClass();
    $slot->bookkingid = $bookking->id;
    $slot->teacherid = $data->teacherid;
    $slot->appointmentlocation = $data->appointmentlocation;
    $slot->exclusivity = $data->exclusivityenable ? $data->exclusivity : 0;
    if ($data->divide) {
        $slot->duration = $data->duration;
    } else {
        $slot->duration = $data->endhour * 60 + $data->endminute - $data->starthour * 60 - $data->startminute;
    };
    $slot->notes = '';
    $slot->notesformat = FORMAT_HTML;
    $slot->timemodified = time();

    for ($d = 0; $d <= $fordays; $d ++) {
        $starttime = $startfrom + ($d * DAYSECS);
        $eventdate = usergetdate($starttime);
        $dayofweek = $eventdate['wday'];
        if ((($dayofweek == 1) && ($data->monday == 1)) ||
        (($dayofweek == 2) && ($data->tuesday == 1)) ||
        (($dayofweek == 3) && ($data->wednesday == 1)) ||
        (($dayofweek == 4) && ($data->thursday == 1)) ||
        (($dayofweek == 5) && ($data->friday == 1)) ||
        (($dayofweek == 6) && ($data->saturday == 1)) ||
        (($dayofweek == 0) && ($data->sunday == 1))) {
            $slot->starttime = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'],
                                              $data->starthour, $data->startminute);
            $data->timestart = $slot->starttime;
            $data->timeend = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'],
                                            $data->endhour, $data->endminute);

            // This corrects around midnight bug.
            if ($data->timestart > $data->timeend) {
                $data->timeend += DAYSECS;
            }
            if ($data->hideuntilrel == 0) {
                $slot->hideuntil = time();
            } else {
                $slot->hideuntil = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 6, 0) -
                                    $data->hideuntilrel;
            }
            if ($data->emaildaterel == -1) {
                $slot->emaildate = 0;
            } else {
                $slot->emaildate = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 0, 0) -
                                    $data->emaildaterel;
            }
            while ($slot->starttime <= $data->timeend - $slot->duration * 60) {
                $conflicts = $bookking->get_conflicts($data->timestart, $data->timestart + $slot->duration * 60,
                                                       $data->teacherid, 0, BOOKKING_ALL);
                $resolvable = (boolean) $data->forcewhenoverlap;
                foreach ($conflicts as $conflict) {
                    $resolvable = $resolvable
                                     && $conflict->isself == 1       // Do not delete slots outside the current bookking.
                                     && $conflict->numstudents == 0; // Do not delete slots with bookings.
                }

                if ($conflicts) {
                    $conflictmsg = '';
                    $cl = new bookking_conflict_list();
                    $cl->add_conflicts($conflicts);
                    if (!$resolvable) {
                        $conflictmsg .= get_string('conflictingslots', 'bookking', userdate($data->timestart));
                        $conflictmsg .= $output->doc_link('mod/bookking/conflict', '', true);
                        $conflictmsg .= $output->render($cl);
                    } else { // We force, so delete all conflicting before inserting.
                        foreach ($conflicts as $conflict) {
                            $cslot = $bookking->get_slot($conflict->id);
                            \mod_bookking\event\slot_deleted::create_from_slot($cslot, 'addsession-conflict')->trigger();
                            $cslot->delete();
                        }
                        $conflictmsg .= get_string('deletedconflictingslots', 'bookking', userdate($data->timestart));
                        $conflictmsg .= $output->doc_link('mod/bookking/conflict', '', true);
                        $conflictmsg .= $output->render($cl);
                    }
                    \core\notification::warning($conflictmsg);
                }
                if (!$conflicts || $resolvable) {
                    $slotid = $DB->insert_record('bookking_slots', $slot, true, true);
                    $slotobj = $bookking->get_slot($slotid);
                    \mod_bookking\event\slot_added::create_from_slot($slotobj)->trigger();
                    $countslots++;
                }
                $slot->starttime += ($slot->duration + $data->break) * 60;
                $data->timestart += ($slot->duration + $data->break) * 60;
            }
        }
    }

    $messagetype = ($countslots > 0) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_INFO;
    $message = get_string('slotsadded', 'bookking', $countslots);
    \core\notification::add($message, $messagetype);

    redirect($returnurl);
}

/**
 * Send a message (confirmed action) after filling the message form
 *
 * @param bookking_instance $bookking
 * @param mixed $formdata
 * @param moodle_url $returnurl the URL to redirect to after the action has been performed
 */
function bookking_action_dosendmessage($bookking, $formdata, $returnurl) {

    global $DB, $USER;

    $data = (object) $formdata;

    $recipients = $data->recipient;
    if ($data->copytomyself) {
        $recipients[$USER->id] = 1;
    }
    $rawmessage = $data->body['text'];
    $format = $data->body['format'];
    $textmessage = format_text_email($rawmessage, $format);
    $htmlmessage = null;
    if ($format == FORMAT_HTML) {
        $htmlmessage = $rawmessage;
    }

    $cnt = 0;
    foreach ($recipients as $recipientid => $value) {
        if ($value) {
            $message = new \core\message\message();
            $message->component = 'mod_bookking';
            $message->name = 'invitation';
            $message->userfrom = $USER;
            $message->userto = $recipientid;
            $message->subject = $data->subject;
            $message->fullmessage = $textmessage;
            $message->fullmessageformat = $format;
            if ($htmlmessage) {
                $message->fullmessagehtml = $htmlmessage;
            }
            $message->notification = '0';

            message_send($message);
            $cnt++;
        }
    }

    $message = get_string('messagesent', 'bookking', $cnt);
    $messagetype = \core\output\notification::NOTIFY_SUCCESS;
    redirect($returnurl, $message, 0, $messagetype);
}

/**
 * Delete slots (after UI button has been pushed)
 *
 * @param bookking_slot[] $slots list of slots to be deleted
 * @param string $action description of the action
 * @param moodle_url $returnurl the URL to redirect to after the action has been performed
 */
function bookking_action_delete_slots(array $slots, $action, moodle_url $returnurl) {
    $cnt = 0;
    foreach ($slots as $slot) {
        \mod_bookking\event\slot_deleted::create_from_slot($slot, $action)->trigger();
        $slot->delete();
        $cnt++;
    }

    if ($cnt == 1) {
        $message = get_string('oneslotdeleted', 'bookking');
    } else {
        $message = get_string('slotsdeleted', 'bookking', $cnt);
    }
    $messagetype = ($cnt > 0) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_INFO;
    \core\notification::add($message, $messagetype);
    redirect($returnurl);
}

// Require valid session key for all actions.
require_sesskey();

// We first have to check whether some action needs to be performed.
// Any of the following actions must issue a redirect when finished.
switch ($action) {
    /************************************ Deleting a slot ***********************************************/
    case 'deleteslot': {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $bookking->get_slot($slotid);
        bookking_action_delete_slots(array($slot), $action, $viewurl);
    }
    /************************************ Deleting multiple slots ***********************************************/
    case 'deleteslots': {
        $slotids = required_param('items', PARAM_SEQUENCE);
        $slotids = explode(",", $slotids);
        $slots = array();
        foreach ($slotids as $slotid) {
            if ($slotid > 0) {
                $slots[] = $bookking->get_slot($slotid);
            }
        }
        bookking_action_delete_slots($slots, $action, $viewurl);
    }
    /************************************ Students were seen ***************************************************/
    case 'saveseen': {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $bookking->get_slot($slotid);
        $seen = optional_param_array('seen', array(), PARAM_INT);

        if (is_array($seen)) {
            foreach ($slot->get_appointments() as $app) {
                $app->attended = (in_array($app->id, $seen)) ? 1 : 0;
                $app->timemodified = time();
            }
        }
        $slot->save();
        redirect($viewurl);
    }
    /************************************ Revoking all appointments to a slot ***************************************/
    case 'revokeall': {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $bookking->get_slot($slotid);

        $oldstudents = array();
        foreach ($slot->get_appointments() as $app) {
            $oldstudents[] = $app->studentid;
            $slot->remove_appointment($app);
        }
        // Notify the student.
        if ($bookking->allownotifications) {
            foreach ($oldstudents as $oldstudent) {
                include_once($CFG->dirroot.'/mod/bookking/mailtemplatelib.php');

                $student = $DB->get_record('user', array('id' => $oldstudent));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));

                bookking_messenger::send_slot_notification($slot, 'bookingnotification', 'teachercancelled',
                                        $teacher, $student, $teacher, $student, $COURSE);
            }
        }

        $slot->save();
        redirect($viewurl);
    }

    /************************************ Toggling to unlimited group ***************************************/
    case 'allowgroup':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->exclusivity = 0;
        $DB->update_record('bookking_slots', $slot);
        redirect($viewurl);
    }

    /************************************ Toggling to single student ******************************************/
    case 'forbidgroup':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->exclusivity = 1;
        $DB->update_record('bookking_slots', $slot);
        redirect($viewurl);
    }

    /************************************ Deleting all slots ***************************************************/
    case 'deleteall':{
        require_capability('mod/bookking:manageallappointments', $context);
        $slots = $bookking->get_all_slots();
        bookking_action_delete_slots($slots, $action, $viewurl);
    }
    /************************************ Deleting unused slots *************************************************/
    case 'deleteunused':{
        $slots = $bookking->get_slots_without_appointment($USER->id);
        bookking_action_delete_slots($slots, $action, $viewurl);
    }
    /************************************ Deleting unused slots (all teachers) ************************************/
    case 'deleteallunused': {
        require_capability('mod/bookking:manageallappointments', $context);
        $slots = $bookking->get_slots_without_appointment();
        bookking_action_delete_slots($slots, $action, $viewurl);
    }
    /************************************ Deleting current teacher's slots ***************************************/
    case 'deleteonlymine': {
        $slots = $bookking->get_slots_for_teacher($USER->id);
        bookking_action_delete_slots($slots, $action, $viewurl);
    }
    /************************************ Mark as seen now *******************************************************/
    case 'markasseennow': {
        $slot = new stdClass();
        $slot->bookkingid = $bookking->id;
        $slot->teacherid = $USER->id;
        $slot->starttime = time();
        $slot->duration = $bookking->defaultslotduration;
        $slot->exclusivity = 1;
        $slot->notes = '';
        $slot->notesformat = FORMAT_HTML;
        $slot->hideuntil = time();
        $slot->appointmentlocation = '';
        $slot->emaildate = 0;
        $slot->timemodified = time();
        $slotid = $DB->insert_record('bookking_slots', $slot);

        $appointment = new stdClass();
        $appointment->slotid = $slotid;
        $appointment->studentid = required_param('studentid', PARAM_INT);
        $appointment->attended = 1;
        $appointment->appointmentnote = '';
        $appointment->appointmentnoteformat = FORMAT_HTML;
        $appointment->teachernote = '';
        $appointment->teachernoteformat = FORMAT_HTML;
        $appointment->timecreated = time();
        $appointment->timemodified = time();
        $DB->insert_record('bookking_appointment', $appointment);

        $slot = $bookking->get_slot($slotid);
        \mod_bookking\event\slot_added::create_from_slot($slot)->trigger();

        redirect($viewurl);
    }
}

/*************************************************************************************************************/

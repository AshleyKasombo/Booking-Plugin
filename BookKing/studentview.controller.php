<?php

/**
 * Controller for student view
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/bookking/mailtemplatelib.php');


function bookking_book_slot($bookking, $slotid, $userid, $groupid, $mform, $formdata, $returnurl) {

    global $DB, $COURSE, $output;

    $slot = $bookking->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    }

    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $requiredcapacity = 1;
    $userstobook = array($userid);
    if ($groupid > 0) {
        if (!$bookking->is_group_scheduling_enabled()) {
            throw new moodle_exception('error');
        }
        $groupmembers = $bookking->get_available_students($groupid);
        $requiredcapacity = count($groupmembers);
        $userstobook = array_keys($groupmembers);
    } else if ($groupid == 0) {
        if (!$bookking->is_individual_scheduling_enabled()) {
            throw new moodle_exception('error');
        }
    } else {
        // Group scheduling enabled but no group selected.
        throw new moodle_exception('error');
    }

    $errormessage = '';

    $bookinglimit = $bookking->count_bookable_appointments($userid, false);
    if ($bookinglimit == 0) {
        $errormessage = get_string('selectedtoomany', 'bookking', $bookinglimit);
    } else {
        // Validate our user ids.
        $existingstudents = array();
        foreach ($slot->get_appointments() as $app) {
            $existingstudents[] = $app->studentid;
        }
        $userstobook = array_diff($userstobook, $existingstudents);

        $remaining = $slot->count_remaining_appointments();
        // If the slot is already overcrowded...
        if ($remaining >= 0 && $remaining < $requiredcapacity) {
            if ($requiredcapacity > 1) {
                $errormessage = get_string('notenoughplaces', 'bookking');
            } else {
                $errormessage = get_string('slot_is_just_in_use', 'bookking');
            }
        }
    }

    if ($errormessage) {
        \core\notification::error($errormessage);
        redirect($returnurl);
    }

    // Create new appointment for each member of the group.
    foreach ($userstobook as $studentid) {
        $appointment = $slot->create_appointment();
        $appointment->studentid = $studentid;
        $appointment->attended = 0;
        $appointment->timecreated = time();
        $appointment->timemodified = time();
        $appointment->save();

        if (($studentid == $userid) && $mform) {
            $mform->save_booking_data($formdata, $appointment);
        }

        \mod_bookking\event\booking_added::create_from_slot($slot)->trigger();

        // Notify the teacher.
        if ($bookking->allownotifications) {
            $student = $DB->get_record('user', array('id' => $appointment->studentid), '*', MUST_EXIST);
            $teacher = $DB->get_record('user', array('id' => $slot->teacherid), '*', MUST_EXIST);
            bookking_messenger::send_slot_notification($slot, 'bookingnotification', 'applied',
                    $student, $teacher, $teacher, $student, $COURSE);
        }
    }
    $slot->save();
    redirect($returnurl);

}

$returnurlparas =  array('id' => $cm->id);
if ($bookking->is_group_scheduling_enabled()) {
    $returnurlparas['appointgroup'] = $appointgroup;
}
$returnurl = new moodle_url('/mod/bookking/view.php', $returnurlparas);


/******************************************** Show the booking form *******************************************/

if ($action == 'bookingform') {
    require_once($CFG->dirroot.'/mod/bookking/bookingform.php');

    require_sesskey();
    require_capability('mod/bookking:appoint', $context);

    $slotid = required_param('slotid', PARAM_INT);
    $slot = $bookking->get_slot($slotid);

    $actionurl = new moodle_url($returnurl, array('what' => 'bookingform', 'slotid' => $slotid));

    $mform = new bookking_booking_form($slot, $actionurl);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if (($formdata = $mform->get_data()) || $appointgroup < 0) {
        // Workaround - call bookking_book_slot also if no group was selected, to show an error message.
        bookking_book_slot($bookking, $slotid, $USER->id, $appointgroup, $mform, $formdata, $returnurl);
        redirect($returnurl);
    } else {
        $groupinfo = null;
        if ($bookking->is_group_scheduling_enabled() && $appointgroup == 0) {
            $groupinfo = get_string('myself', 'bookking');
        } else if ($appointgroup > 0) {
            $groupinfo = $mygroupsforscheduling[$appointgroup]->name;
        }

        echo $output->header();
        echo $output->heading(get_string('bookaslot', 'bookking'));
        echo $output->box(format_text($bookking->intro, $bookking->introformat));

        $info = bookking_appointment_info::make_from_slot($slot, true, true, $groupinfo);
        echo $output->render($info);
        $mform->display();
        echo $output->footer();
        exit();
    }

}

/************************************************ Book a slot  ************************************************/

if ($action == 'bookslot') {

    require_sesskey();
    require_capability('mod/bookking:appoint', $context);

    // Reject this request if the user is required to go through a booking form.
    if ($bookking->uses_bookingform()) {
        throw new moodle_exception('error');
    }

    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);

    bookking_book_slot($bookking, $slotid, $USER->id, $appointgroup, null, null, $returnurl);
}

/******************************************** Show details of booking *******************************************/

if ($action == 'viewbooking') {
    require_once($CFG->dirroot.'/mod/bookking/bookingform.php');

    require_sesskey();
    require_capability('mod/bookking:appoint', $context);

    $appointmentid = required_param('appointmentid', PARAM_INT);
    list($slot, $appointment) = $bookking->get_slot_appointment($appointmentid);

    if ($appointment->studentid != $USER->id) {
        throw new moodle_exception('nopermissions');
    }

    echo $output->header();
    echo $output->heading(get_string('bookingdetails', 'bookking'));
    echo $output->mod_intro($bookking);
    $info = bookking_appointment_info::make_from_appointment($slot, $appointment);
    echo $output->render($info);

    echo $output->continue_button($returnurl);
    echo $output->footer();
    exit();

}

/******************************************** Edit a booking *******************************************/

if ($action == 'editbooking') {
    require_once($CFG->dirroot.'/mod/bookking/bookingform.php');

    require_sesskey();
    require_capability('mod/bookking:appoint', $context);

    if (!$bookking->uses_studentdata()) {
        throw new moodle_exception('error');
    }

    $appointmentid = required_param('appointmentid', PARAM_INT);
    list($slot, $appointment) = $bookking->get_slot_appointment($appointmentid);

    if ($appointment->studentid != $USER->id) {
        throw new moodle_exception('nopermissions');
    }
    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $actionurl = new moodle_url($returnurl, array('what' => 'editbooking', 'appointmentid' => $appointmentid));

    $mform = new bookking_booking_form($slot, $actionurl, true);
    $mform->set_data($mform->prepare_booking_data($appointment));

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_booking_data($formdata, $appointment);
        redirect($returnurl);
    } else {
        echo $output->header();
        echo $output->heading(get_string('editbooking', 'bookking'));
        echo $output->box(format_text($bookking->intro, $bookking->introformat));
        $info = bookking_appointment_info::make_from_slot($slot);
        echo $output->render($info);
        $mform->display();
        echo $output->footer();
        exit();
    }

}


/******************************** Cancel a booking (for the current student or a group) ******************************/

if ($action == 'cancelbooking') {

    require_sesskey();
    require_capability('mod/bookking:appoint', $context);

    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);
    $slot = $bookking->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    }

    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $userstocancel = array($USER->id);
    if ($appointgroup) {
        $userstocancel = array_keys($bookking->get_available_students($appointgroup));
    }

    foreach ($userstocancel as $userid) {
        if ($appointment = $slot->get_student_appointment($userid)) {
            $bookking->delete_appointment($appointment->id);

            // Notify the teacher.
            if ($bookking->allownotifications) {
                $student = $DB->get_record('user', array('id' => $USER->id));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
                bookking_messenger::send_slot_notification($slot, 'bookingnotification', 'cancelled',
                                                            $student, $teacher, $teacher, $student, $COURSE);
            }
            \mod_bookking\event\booking_removed::create_from_slot($slot)->trigger();
        }
    }
    redirect($returnurl);

}

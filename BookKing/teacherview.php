<?php

/**
 * Contains various sub-screens that a teacher can see.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Print a selection box of existing slots to be bookking in
 *
 * @param bookking_instance $bookking
 * @param int $studentid student to schedule
 * @param int $groupid group to schedule
 */
function bookking_print_schedulebox(bookking_instance $bookking, $studentid, $groupid = 0) {
    global $output;

    $availableslots = $bookking->get_slots_available_to_student($studentid);

    $startdatemem = '';
    $starttimemem = '';
    $availableslotsmenu = array();
    foreach ($availableslots as $slot) {
        $startdatecnv = $output->userdate($slot->starttime);
        $starttimecnv = $output->usertime($slot->starttime);

        $startdatestr = ($startdatemem != '' and $startdatemem == $startdatecnv) ? "-----------------" : $startdatecnv;
        $starttimestr = ($starttimemem != '' and $starttimemem == $starttimecnv) ? '' : $starttimecnv;

        $startdatemem = $startdatecnv;
        $starttimemem = $starttimecnv;

        $url = new moodle_url('/mod/bookking/view.php',
                        array('id' => $bookking->cmid, 'slotid' => $slot->id, 'sesskey' => sesskey()));
        if ($groupid) {
            $url->param('what', 'schedulegroup');
            $url->param('subaction', 'dochooseslot');
            $url->param('groupid', $groupid);
        } else {
            $url->param('what', 'schedule');
            $url->param('subaction', 'dochooseslot');
            $url->param('studentid', $studentid);
        }
        $availableslotsmenu[$url->out()] = "$startdatestr $starttimestr";
    }

    $chooser = new url_select($availableslotsmenu);

    if ($availableslots) {
        echo $output->box_start();
        echo $output->heading(get_string('chooseexisting', 'bookking'), 3);
        echo $output->render($chooser);
        echo $output->box_end();
    }
}

// Load group restrictions.
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = false;
if ($groupmode) {
    $currentgroup = groups_get_activity_group($cm, true);
}

// All group arrays in the following are in the format used by groups_get_all_groups.
// The special value '' (empty string) is used to signal "all groups" (no restrictions).

// Find groups which the current teacher can see ($groupsicansee, $groupsicurrentlysee).
// $groupsicansee contains all groups that a teacher potentially has access to.
// $groupsicurrentlysee may be restricted by the user to one group, using the drop-down box.
$userfilter = $USER->id;
if (has_capability('moodle/site:accessallgroups', $context)) {
    $userfilter = 0;
}
$groupsicansee = '';
$groupsicurrentlysee = '';
if ($groupmode) {
    if ($userfilter) {
        $groupsicansee = groups_get_all_groups($COURSE->id, $userfilter, $cm->groupingid);
    }
    $groupsicurrentlysee = $groupsicansee;
    if ($currentgroup) {
        if ($userfilter && !groups_is_member($currentgroup, $userfilter)) {
            $groupsicurrentlysee = array();
        } else {
            $cgobj = groups_get_group($currentgroup);
            $groupsicurrentlysee = array($currentgroup => $cgobj);
        }
    }
}

// Find groups which the current teacher can schedule as a group ($groupsicanschedule).
$groupsicanschedule = array();
if ($bookking->is_group_scheduling_enabled()) {
    $groupsicanschedule = groups_get_all_groups($COURSE->id, $userfilter, $bookking->bookingrouping);
}

// Find groups which can book an appointment with the current teacher ($groupsthatcanseeme).

$groupsthatcanseeme = '';
if ($groupmode) {
    $groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);
}


$taburl = new moodle_url('/mod/bookking/view.php', array('id' => $bookking->cmid, 'what' => 'view', 'subpage' => $subpage));

$baseurl = new moodle_url('/mod/bookking/view.php', array(
        'id' => $bookking->cmid,
        'subpage' => $subpage,
        'offset' => $offset
));

// The URL that is used for jumping back to the view (e.g., after an action is performed).
$viewurl = new moodle_url($baseurl, array('what' => 'view'));

$PAGE->set_url($viewurl);

if ($action != 'view') {
    require_once($CFG->dirroot.'/mod/bookking/slotforms.php');
    include($CFG->dirroot.'/mod/bookking/teacherview.controller.php');
}

/************************************ View : New single slot form ****************************************/
if ($action == 'addslot') {
    $actionurl = new moodle_url($baseurl, array('what' => 'addslot'));

    if (!$bookking->has_available_teachers()) {
        print_error('needteachers', 'bookking', viewurl);
    }

    $mform = new bookking_editslot_form($actionurl, $bookking, $cm, $groupsicansee);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        $slot = $mform->save_slot(0, $formdata);
        \mod_bookking\event\slot_added::create_from_slot($slot)->trigger();
        redirect($viewurl,
                 get_string('oneslotadded', 'bookking'),
                 0,
                 \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $output->header();
        echo $output->heading(get_string('addsingleslot', 'bookking'));
        $mform->display();
        echo $output->footer($course);
        die;
    }
}
/************************************ View : Update single slot form ****************************************/
if ($action == 'updateslot') {

    $slotid = required_param('slotid', PARAM_INT);
    $slot = $bookking->get_slot($slotid);
    if ($slot->starttime % 300 !== 0 || $slot->duration % 5 !== 0) {
        $timeoptions = array('step' => 1, 'optional' => false);
    } else {
        $timeoptions = array('step' => 5, 'optional' => false);
    }

    $actionurl = new moodle_url($baseurl, array('what' => 'updateslot', 'slotid' => $slotid));

    $mform = new bookking_editslot_form($actionurl, $bookking, $cm, $groupsicansee, array(
            'slotid' => $slotid,
            'timeoptions' => $timeoptions)
        );
    $data = $mform->prepare_formdata($slot);
    $mform->set_data($data);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_slot($slotid, $formdata);
        redirect($viewurl,
                 get_string('slotupdated', 'bookking'),
                 0,
                 \core\output\notification::NOTIFY_SUCCESS);
    } else {
        echo $output->header();
        echo $output->heading(get_string('updatesingleslot', 'bookking'));
        $mform->display();
        echo $output->footer($course);
        die;
    }

}
/************************************ Add session multiple slots form ****************************************/
if ($action == 'addsession') {

    $actionurl = new moodle_url($baseurl, array('what' => 'addsession'));

    if (!$bookking->has_available_teachers()) {
        print_error('needteachers', 'bookking', $viewurl);
    }

    $mform = new bookking_addsession_form($actionurl, $bookking, $cm, $groupsicansee);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        bookking_action_doaddsession($bookking, $formdata, $viewurl);
    } else {
        echo $output->header();
        echo $output->heading(get_string('addsession', 'bookking'));
        $mform->display();
        echo $output->footer();
        die;
    }
}

/************************************ Schedule a student form ***********************************************/
if ($action == 'schedule') {
    echo $output->header();

    if ($subaction == 'dochooseslot') {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $bookking->get_slot($slotid);
        $studentid = required_param('studentid', PARAM_INT);

        $actionurl = new moodle_url($baseurl, array('what' => 'updateslot', 'slotid' => $slotid));

        $repeats = $slot->get_appointment_count() + 1;
        $mform = new bookking_editslot_form($actionurl, $bookking, $cm, $groupsicansee,
                                             array('slotid' => $slotid, 'repeats' => $repeats));
        $data = $mform->prepare_formdata($slot);
        $data->studentid[] = $studentid;
        $mform->set_data($data);

        echo $output->heading(get_string('updatesingleslot', 'bookking'), 2);
        $mform->display();

    } else if (empty($subaction)) {
        $studentid = required_param('studentid', PARAM_INT);
        $student = $DB->get_record('user', array('id' => $studentid), '*', MUST_EXIST);

        $actionurl = new moodle_url($baseurl, array('what' => 'addslot'));

        $mform = new bookking_editslot_form($actionurl, $bookking, $cm, $groupsicansee);

        $data = array();
        $data['studentid'][0] = $studentid;
        $mform->set_data($data);

        echo $output->heading(get_string('scheduleappointment', 'bookking', fullname($student)));

        bookking_print_schedulebox($bookking, $studentid);

        echo $output->box_start();
        echo $output->heading(get_string('scheduleinnew', 'bookking'), 3);
        $mform->display();
        echo $output->box_end();
    }

    echo $output->footer();
    die();
}
/************************************ Schedule a whole group in form ***********************************************/
if ($action == 'schedulegroup') {

    $groupid = required_param('groupid', PARAM_INT);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);

    echo $output->header();

    if ($subaction == 'dochooseslot') {

        $slotid = required_param('slotid', PARAM_INT);
        $groupid = required_param('groupid', PARAM_INT);
        $slot = $bookking->get_slot($slotid);

        $actionurl = new moodle_url($baseurl, array('what' => 'updateslot', 'slotid' => $slotid));

        $repeats = $slot->get_appointment_count() + count($members);
        $mform = new bookking_editslot_form($actionurl, $bookking, $cm, $groupsicansee,
                                             array('slotid' => $slotid, 'repeats' => $repeats));
        $data = $mform->prepare_formdata($slot);
        foreach ($members as $member) {
            $data->studentid[] = $member->id;
        }
        $mform->set_data($data);

        echo $output->heading(get_string('updatesingleslot', 'bookking'), 3);
        $mform->display();

    } else if (empty($subaction)) {

        $actionurl = new moodle_url($baseurl, array('what' => 'addslot'));

        $data = array();
        $i = 0;
        foreach ($members as $member) {
            $data['studentid'][$i] = $member->id;
            $i++;
        }
        $data['exclusivity'] = $i;

        $mform = new bookking_editslot_form($actionurl, $bookking, $cm, $groupsicansee, array('repeats' => $i));
        $mform->set_data($data);

        echo $output->heading(get_string('scheduleappointment', 'bookking', $group->name));

        bookking_print_schedulebox($bookking, 0, $groupid);

        echo $output->box_start();
        echo $output->heading(get_string('scheduleinnew', 'bookking'), 3);
        $mform->display();
        echo $output->box_end();

    }
    echo $output->footer();
    die();
}

/************************************ Send message to students ****************************************/
if ($action == 'sendmessage') {
    require_once($CFG->dirroot.'/mod/bookking/message_form.php');

    $template = optional_param('template', 'none', PARAM_ALPHA);
    $recipientids = required_param('recipients', PARAM_SEQUENCE);

    $actionurl = new moodle_url('/mod/bookking/view.php',
            array('what' => 'sendmessage', 'id' => $cm->id, 'subpage' => $subpage,
                  'template' => $template, 'recipients' => $recipientids));

    $templatedata = array();
    if ($template != 'none') {
        $vars = bookking_messenger::get_bookking_variables($bookking, null, $USER, null, $COURSE, null);
        $templatedata['subject'] = bookking_messenger::compile_mail_template($template, 'subject', $vars);
        $templatedata['body'] = bookking_messenger::compile_mail_template($template, 'html', $vars);
    }
    $templatedata['recipients'] = $DB->get_records_list('user', 'id', explode(',', $recipientids), 'lastname,firstname');

    $mform = new bookking_message_form($actionurl, $bookking, $templatedata);

    if ($mform->is_cancelled()) {
        redirect($viewurl);
    } else if ($formdata = $mform->get_data()) {
        bookking_action_dosendmessage($bookking, $formdata, $viewurl);
    } else {
        echo $output->header();
        echo $output->heading(get_string('sendmessage', 'bookking'));
        $mform->display();
        echo $output->footer();
        die;
    }
}


/****************** Standard view ***********************************************/


// Trigger view event.
\mod_bookking\event\appointment_list_viewed::create_from_bookking($bookking)->trigger();


// Print top tabs.

$actionurl = new moodle_url($viewurl, array('sesskey' => sesskey()));

$inactive = array();
if ($DB->count_records('bookking_slots', array('bookkingid' => $bookking->id)) <=
         $DB->count_records('bookking_slots', array('bookkingid' => $bookking->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this bookking.
    $inactive[] = 'allappointments';
    if ($subpage = 'allappointments') {
        $subpage = 'myappointments';
    }
}

echo $output->header();

echo $output->teacherview_tabs($bookking, $taburl, $subpage, $inactive);
if ($groupmode) {
    if ($subpage == 'allappointments') {
        groups_print_activity_menu($cm, $taburl);
    } else {
        $a = new stdClass();
        $a->groupmode = get_string($groupmode == VISIBLEGROUPS ? 'groupsvisible' : 'groupsseparate');
        $groupnames = array();
        foreach ($groupsthatcanseeme as $id => $group) {
            $groupnames[] = $group->name;
        }
        $a->grouplist = implode(', ', $groupnames);
        $messagekey = $groupsthatcanseeme ? 'groupmodeyourgroups' : 'groupmodeyourgroupsempty';
        $message = get_string($messagekey, 'bookking', $a);
        echo html_writer::div($message, 'groupmodeyourgroups');
    }
}

// Print intro.
echo $output->mod_intro($bookking);


if ($subpage == 'allappointments') {
    $teacherid = 0;
    $slotgroup = $currentgroup;
} else {
    $teacherid = $USER->id;
    $slotgroup = 0;
    $subpage = 'myappointments';
}
$sqlcount = $bookking->count_slots_for_teacher($teacherid, $slotgroup);

$pagesize = 25;
if ($offset == -1) {
    if ($sqlcount > $pagesize) {
        $offsetcount = $bookking->count_slots_for_teacher($teacherid, $slotgroup, true);
        $offset = floor($offsetcount / $pagesize);
    } else {
        $offset = 0;
    }
}
if ($offset * $pagesize >= $sqlcount && $sqlcount > 0) {
    $offset = floor(($sqlcount-1) / $pagesize);
}

$slots = $bookking->get_slots_for_teacher($teacherid, $slotgroup, $offset * $pagesize, $pagesize);

echo $output->heading(get_string('slots', 'bookking'));

// Print instructions and button for creating slots.
$key = ($slots) ? 'addslot' : 'welcomenewteacher';
echo html_writer::div(get_string($key, 'bookking'));


$commandbar = new bookking_command_bar();
$commandbar->title = get_string('actions', 'bookking');

$addbuttons = array();
$addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'addsession')), 'addsession', 't/add');
$addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'addslot')), 'addsingleslot', 't/add');
$commandbar->add_group(get_string('addcommands', 'bookking'), $addbuttons);

// If slots already exist, also show delete buttons.
if ($slots) {
    $delbuttons = array();

    $delselectedurl = new moodle_url($actionurl, array('what' => 'deleteslots'));
    $PAGE->requires->yui_module('moodle-mod_bookking-delselected', 'M.mod_bookking.delselected.init',
                                array($delselectedurl->out(false)) );
    $delselected = $commandbar->action_link($delselectedurl, 'deleteselection', 't/delete',
                                            'confirmdelete-selected', 'delselected');
    $delselected->formid = 'delselected';
    $delbuttons[] = $delselected;

    if (has_capability('mod/bookking:manageallappointments', $context) && $subpage == 'allappointments') {
        $delbuttons[] = $commandbar->action_link(
                        new moodle_url($actionurl, array('what' => 'deleteall')),
                        'deleteallslots', 't/delete', 'confirmdelete-all');
        $delbuttons[] = $commandbar->action_link(
                        new moodle_url($actionurl, array('what' => 'deleteallunused')),
                        'deleteallunusedslots', 't/delete', 'confirmdelete-unused');
    }
    $delbuttons[] = $commandbar->action_link(
                    new moodle_url($actionurl, array('what' => 'deleteunused')),
                    'deleteunusedslots', 't/delete', 'confirmdelete-myunused');
    $delbuttons[] = $commandbar->action_link(
                    new moodle_url($actionurl, array('what' => 'deleteonlymine')),
                    'deletemyslots', 't/delete', 'confirmdelete-mine');

    $commandbar->add_group(get_string('deletecommands', 'bookking'), $delbuttons);
}

echo $output->render($commandbar);


// Some slots already exist - prepare the table of slots.
if ($slots) {

    $slotman = new bookking_slot_manager($bookking, $actionurl);
    $slotman->showteacher = ($subpage == 'allappointments');

    foreach ($slots as $slot) {

        $editable = ($USER->id == $slot->teacherid || has_capability('mod/bookking:manageallappointments', $context));

        $studlist = new bookking_student_list($slotman->bookking);
        $studlist->expandable = false;
        $studlist->expanded = true;
        $studlist->editable = $editable;
        $studlist->linkappointment = true;
        $studlist->checkboxname = 'seen[]';
        $studlist->buttontext = get_string('saveseen', 'bookking');
        $studlist->actionurl = new moodle_url($actionurl, array('what' => 'saveseen', 'slotid' => $slot->id));
        foreach ($slot->get_appointments() as $app) {
            $studlist->add_student($app, false, $app->is_attended(), true, $bookking->uses_studentdata());
        }

        $slotman->add_slot($slot, $studlist, $editable);
    }

    echo $output->render($slotman);

    if ($sqlcount > $pagesize) {
        echo $output->paging_bar($sqlcount, $offset, $pagesize, $actionurl, 'offset');
    }

    // Instruction for teacher to click Seen box after appointment.
    echo html_writer::div(get_string('markseen', 'bookking'));

}

$groupfilter = ($subpage == 'myappointments') ? $groupsthatcanseeme : $groupsicurrentlysee;
$maxlistsize = get_config('mod_bookking', 'maxstudentlistsize');
$students = array();
$reminderstudents = array();
if ($groupfilter === '') {
    $students = $bookking->get_students_for_scheduling('', $maxlistsize);
    if ($bookking->allows_unlimited_bookings()) {
        $reminderstudents  = $bookking->get_students_for_scheduling('', $maxlistsize, true);
    } else {
        $reminderstudents = $students;
    }
} else if (count($groupfilter) > 0) {
    $students = $bookking->get_students_for_scheduling(array_keys($groupfilter), $maxlistsize);
    if ($bookking->allows_unlimited_bookings()) {
        $reminderstudents = $bookking->get_students_for_scheduling(array_keys($groupfilter), $maxlistsize, true);
    } else {
        $reminderstudents = $students;
    }
}

if ($students === 0) {
    $nostudentstr = get_string('noexistingstudents', 'bookking');
    if ($COURSE->id == SITEID) {
        $nostudentstr .= '<br/>'.get_string('howtoaddstudents', 'bookking');
    }
    echo $output->notification($nostudentstr, 'notifyproblem');
} else if (is_integer($students)) {
    // There are too many students who still have to make appointments, don't display a list.
    $toomanystr = get_string('missingstudentsmany', 'bookking', $students);
    echo $output->notification($toomanystr, 'notifymessage');

} else if (count($students) > 0) {

    if (count($reminderstudents) > 0) {
        $studids = implode(',', array_keys($reminderstudents));

        $messageurl = new moodle_url($actionurl, array('what' => 'sendmessage', 'recipients' => $studids));
        $invitationurl = new moodle_url($messageurl, array('template' => 'invite'));
        $reminderurl = new moodle_url($messageurl, array('template' => 'invitereminder'));

        $maildisplay = '';
        $maildisplay .= html_writer::link($invitationurl, get_string('sendinvitation', 'bookking'));
        $maildisplay .= ' &mdash; ';
        $maildisplay .= html_writer::link($reminderurl, get_string('sendreminder', 'bookking'));

        echo $output->box_start('maildisplay');
        // Print number of students who still have to make an appointment.
        echo $output->heading(get_string('missingstudents', 'bookking', count($reminderstudents)), 3);
        // Print e-mail addresses and mailto links.
        echo $maildisplay;
        echo $output->box_end();
    }

    $userfields = bookking_get_user_fields(null, $context);
    $fieldtitles = array();
    foreach ($userfields as $f) {
        $fieldtitles[] = $f->title;
    }
    $studtable = new bookking_scheduling_list($bookking, $fieldtitles);
    $studtable->id = 'studentstoschedule';

    foreach ($students as $student) {
        $picture = $output->user_picture($student);
        $name = $output->user_profile_link($bookking, $student);
        $actions = array();
        $actions[] = new action_menu_link_secondary(
                        new moodle_url($actionurl, array('what' => 'schedule', 'studentid' => $student->id)),
                        new pix_icon('e/insert_date', '', 'moodle'),
                        get_string('scheduleinslot', 'bookking') );
        $actions[] = new action_menu_link_secondary(
                        new moodle_url($actionurl, array('what' => 'markasseennow', 'studentid' => $student->id)),
                        new pix_icon('t/approve', '', 'moodle'),
                        get_string('markasseennow', 'bookking') );

        $userfields = bookking_get_user_fields($student, $context);
        $fieldvals = array();
        foreach ($userfields as $f) {
            $fieldvals[] = $f->value;
        }
        $studtable->add_line($picture, $name, $fieldvals, $actions);
    }

    $divclass = 'schedulelist '.($bookking->is_group_scheduling_enabled() ? 'halfsize' : 'fullsize');
    echo html_writer::start_div($divclass);
    echo $output->heading(get_string('schedulestudents', 'bookking'), 3);

    // Print table of students who still have to make appointments.
    echo $output->render($studtable);
    echo html_writer::end_div();

    if ($bookking->is_group_scheduling_enabled()) {

        // Print list of groups that can be scheduled.

        echo html_writer::start_div('schedulelist halfsize');
        echo $output->heading(get_string('schedulegroups', 'bookking'), 3);

        if (empty($groupsicanschedule)) {
            echo $output->notification(get_string('nogroups', 'bookking'));
        } else {
            $grouptable = new bookking_scheduling_list($bookking, array());
            $grouptable->id = 'groupstoschedule';

            $groupcnt = 0;
            foreach ($groupsicanschedule as $group) {
                $members = groups_get_members($group->id, user_picture::fields('u'), 'u.lastname, u.firstname');
                if (empty($members)) {
                    continue;
                }
                if (!$bookking->has_slots_booked_for_group($group->id, false, $bookking->bookkingmode == 'onetime')) {

                    $picture = print_group_picture($group, $course->id, false, true, true);
                    $name = $group->name;
                    $groupmembers = array();
                    foreach ($members as $member) {
                        $groupmembers[] = fullname($member);
                    }
                    $name .= ' ['. implode(', ', $groupmembers) . ']';
                    $actions = array();
                    $actions[] = new action_menu_link_secondary(
                                    new moodle_url($actionurl, array('what' => 'schedulegroup', 'groupid' => $group->id)),
                                    new pix_icon('e/insert_date', '', 'moodle'),
                                    get_string('scheduleinslot', 'bookking') );

                    $grouptable->add_line($picture, $name, array(), $actions);
                    $groupcnt++;
                }
            }
            // Print table of groups that still need to make appointments.
            if ($groupcnt > 0) {
                echo $output->render($grouptable);
            } else {
                echo $output->notification(get_string('nogroups', 'bookking'));
            }
        }
        echo html_writer::end_div();
    }

} else {
    echo $output->notification(get_string('noexistingstudents', 'bookking'));
}
echo $output->footer();

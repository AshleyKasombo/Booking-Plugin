<?php

/**
 * Prints the screen that displays a single student to a teacher.
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/bookking/locallib.php');

if (!has_capability('mod/bookking:manage', $context)) {
    require_capability('mod/bookking:manageallappointments', $context);
}

$appointmentid = required_param('appointmentid', PARAM_INT);
list($slot, $appointment) = $bookking->get_slot_appointment($appointmentid);
$studentid = $appointment->studentid;

$urlparas = array('what' => 'viewstudent',
    'id' => $bookking->cmid,
    'appointmentid' => $appointmentid,
    'course' => $bookking->courseid);
$taburl = new moodle_url('/mod/bookking/view.php', $urlparas);
$PAGE->set_url($taburl);

$appts = $bookking->get_appointments_for_student($studentid);

$pages = array('thisappointment');
if ($slot->get_appointment_count() > 1) {
    $pages[] = 'otherstudents';
}
if (count($appts) > 1) {
    $pages[] = 'otherappointments';
}

if (!in_array($subpage, $pages) ) {
    $subpage = 'thisappointment';
}

// Process edit form before page output starts.
if ($subpage == 'thisappointment') {
    require_once($CFG->dirroot.'/mod/bookking/appointmentforms.php');

    $actionurl = new moodle_url($taburl, array('page' => 'thisappointment'));
    $returnurl = new moodle_url($taburl, array('page' => 'thisappointment'));

    $distribute = ($slot->get_appointment_count() > 1);
    $gradeedit = ($slot->teacherid == $USER->id) || get_config('mod_bookking', 'allteachersgrading');
    $mform = new bookking_editappointment_form($appointment, $actionurl, $gradeedit, $distribute);
    $mform->set_data($mform->prepare_appointment_data($appointment));

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_appointment_data($formdata, $appointment);
        redirect($returnurl);
    }
}

echo $output->header();

// Print user summary.

bookking_print_user($DB->get_record('user', array('id' => $appointment->studentid)), $course);

// Print tabs.
$tabrows = array();
$row  = array();

if (count($pages) > 1) {
    foreach ($pages as $tabpage) {
        $tabname = get_string('tab-'.$tabpage, 'bookking');
        $row[] = new tabobject($tabpage, new moodle_url($taburl, array('subpage' => $tabpage)), $tabname);
    }
    $tabrows[] = $row;
    print_tabs($tabrows, $subpage);
}

$totalgradeinfo = new bookking_totalgrade_info($bookking, $bookking->get_gradebook_info($appointment->studentid));

if ($subpage == 'thisappointment') {

    $ai = bookking_appointment_info::make_for_teacher($slot, $appointment);
    echo $output->render($ai);

    $mform->display();

    if ($bookking->uses_grades()) {
        echo $output->render($totalgradeinfo);
    }

} else if ($subpage == 'otherappointments') {
    // Print table of other appointments of the same student.

    $studenturl = new moodle_url($taburl, array('page' => 'thisappointment'));
    $table = new bookking_slot_table($bookking, true, $studenturl);
    $table->showattended = true;
    $table->showteachernotes = true;
    $table->showeditlink = true;
    $table->showlocation = false;

    foreach ($appts as $appt) {
        $table->add_slot($appt->get_slot(), $appt, null, false);
    }

    echo $output->render($table);

    if ($bookking->uses_grades()) {
        $totalgradeinfo->showtotalgrade = true;
        $totalgradeinfo->totalgrade = $bookking->get_user_grade($appointment->studentid);
        echo $output->render($totalgradeinfo);
    }

} else if ($subpage == 'otherstudents') {
    // Print table of other students in the same slot.

    $ai = bookking_appointment_info::make_from_slot($slot, false);
    echo $output->render($ai);

    $studenturl = new moodle_url($taburl, array('page' => 'thisappointment'));
    $table = new bookking_slot_table($bookking, true, $studenturl);
    $table->showattended = true;
    $table->showslot = false;
    $table->showstudent = true;
    $table->showteachernotes = true;
    $table->showeditlink = true;

    foreach ($slot->get_appointments() as $otherappointment) {
        $table->add_slot($otherappointment->get_slot(), $otherappointment, null, false);
    }

    echo $output->render($table);
}

echo $output->continue_button(new moodle_url('/mod/bookking/view.php', array('id' => $bookking->cmid)));
echo $output->footer($course);
exit;

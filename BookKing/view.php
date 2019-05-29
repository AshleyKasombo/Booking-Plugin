<?php

/**
 * This page prints a particular instance of bookking and handles
 * top level interactions
 *
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/bookking/lib.php');
require_once($CFG->dirroot.'/mod/bookking/locallib.php');
require_once($CFG->dirroot.'/mod/bookking/renderable.php');

// Read common request parameters.
$id = optional_param('id', '', PARAM_INT);    // Course Module ID - if it's not specified, must specify 'a', see below.
$action = optional_param('what', 'view', PARAM_ALPHA);
$subaction = optional_param('subaction', '', PARAM_ALPHA);
$offset = optional_param('offset', -1, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('bookking', $id, 0, false, MUST_EXIST);
    $bookking = bookking_instance::load_by_coursemodule_id($id);
} else {
    $a = required_param('a', PARAM_INT);     // BookKing ID.
    $bookking = bookking_instance::load_by_id($a);
    $cm = $bookking->get_cm();
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$defaultsubpage = groups_get_activity_groupmode($cm) ? 'myappointments' : 'allappointments';
$subpage = optional_param('subpage', $defaultsubpage, PARAM_ALPHA);

require_login($course->id, true, $cm);
$context = context_module::instance($cm->id);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/bookking/view.php', array('id' => $cm->id));

$output = $PAGE->get_renderer('mod_bookking');

// Print the page header.

$title = $course->shortname . ': ' . format_string($bookking->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);


// Route to screen.

$isteacher = has_capability('mod/bookking:manage', $context);
$isstudent = has_capability('mod/bookking:viewslots', $context);
if ($isteacher) {
    // Teacher side.
    if ($action == 'viewstatistics') {
        include($CFG->dirroot.'/mod/bookking/viewstatistics.php');
    } else if ($action == 'viewstudent') {
        include($CFG->dirroot.'/mod/bookking/viewstudent.php');
    } else if ($action == 'export') {
        include($CFG->dirroot.'/mod/bookking/export.php');
    } else if ($action == 'datelist') {
        include($CFG->dirroot.'/mod/bookking/datelist.php');
    } else {
        include($CFG->dirroot.'/mod/bookking/teacherview.php');
    }

} else if ($isstudent) {
    // Student side.
    include($CFG->dirroot.'/mod/bookking/studentview.php');

} else {
    // For guests.
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('guestscantdoanything', 'bookking'), 'generalbox');
    echo $OUTPUT->footer($course);
}

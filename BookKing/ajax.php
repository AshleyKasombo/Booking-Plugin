<?php

define('AJAX_SCRIPT', true);

#require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$cm = get_coursemodule_from_id('bookking', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$bookking = bookking_instance::load_by_coursemodule_id($id);

require_login($course, true, $cm);
require_sesskey();

$return = 'OK';

switch ($action) {
    case 'saveseen':

        $appid = required_param('appointmentid', PARAM_INT);
        $slotid = $DB->get_field('bookking_appointment', 'slotid', array('id' => $appid));
        $slot = $bookking->get_slot($slotid);
        $app = $slot->get_appointment($appid);
        $newseen = required_param('seen', PARAM_BOOL);

        if ($USER->id != $slot->teacherid) {
            require_capability('mod/bookking:manageallappointments', $bookking->context);
        }

        $app->attended = $newseen;
        $slot->save();

        break;
}

echo json_encode($return);
die;

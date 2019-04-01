<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_bookking.
 *
 * @package     mod_bookking
 * @copyright   2019 Shivaar Sooklal <shivaarsooklal.108@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$b  = optional_param('b', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('bookking', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('mod_bookking', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($b) {
    $moduleinstance = $DB->get_record('mod_bookking', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('bookking', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', mod_bookking));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_bookking\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mod_bookking', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/bookking/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

echo $OUTPUT->footer();

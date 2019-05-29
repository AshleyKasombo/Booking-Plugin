<?PHP



require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // Course id.
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$PAGE->set_url('/mod/bookking/index.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');

$coursecontext = context_course::instance($id);
require_login($course->id);

$event = \mod_bookking\event\course_module_instance_list_viewed::create(array(
    'context' => $coursecontext
));
$event->add_record_snapshot('course', $course);
$event->trigger();

// Get all required strings.

$strbookkings = get_string('modulenameplural', 'bookking');
$strbookking  = get_string('modulename', 'bookking');

// Print the header.

$title = $course->shortname . ': ' . $strbookkings;
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header($course);


// Get all the appropriate data.

if (!$bookkings = get_all_instances_in_course('bookking', $course)) {
    notice(get_string('nobookkings', 'bookking'), "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances.

$timenow = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic  = get_string('topic');

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('CENTER', 'LEFT');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('CENTER', 'LEFT', 'LEFT', 'LEFT');
} else {
    $table->head  = array ($strname);
    $table->align = array ('LEFT', 'LEFT', 'LEFT');
}

foreach ($bookkings as $bookking) {
    $url = new moodle_url('/mod/bookking/view.php', array('id' => $bookking->coursemodule));
    // Show dimmed if the mod is hidden.
    $attr = $bookking->visible ? null : array('class' => 'dimmed');
    $link = html_writer::link($url, $bookking->name, $attr);
    if ($bookking->visible or has_capability('moodle/course:viewhiddenactivities', $coursecontext)) {
        if ($course->format == 'weeks' or $course->format == 'topics') {
            $table->data[] = array ($bookking->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }
}

echo html_writer::table($table);

// Finish the page.

echo $OUTPUT->footer($course);


<?php

//Shows a sortable list of appointments 
 

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

$PAGE->set_docs_path('mod/bookking/datelist');

$scope = optional_param('scope', 'activity', PARAM_TEXT);
if (!in_array($scope, array('activity', 'course', 'site'))) {
    $scope = 'activity';
}
$teacherid = optional_param('teacherid', 0, PARAM_INT);

if ($scope == 'site') {
    $scopecontext = context_system::instance();
} else if ($scope == 'course') {
    $scopecontext = context_course::instance($bookking->courseid);
} else {
    $scopecontext = $context;
}

if (!has_capability('mod/bookking:seeoverviewoutsideactivity', $context)) {
    $scope = 'activity';
}
if (!has_capability('mod/bookking:canseeotherteachersbooking', $scopecontext)) {
    $teacherid = 0;
}

$taburl = new moodle_url('/mod/bookking/view.php',
                array('id' => $bookking->cmid, 'what' => 'datelist', 'scope' => $scope, 'teacherid' => $teacherid));
$returnurl = new moodle_url('/mod/bookking/view.php', array('id' => $bookking->cmid));

$PAGE->set_url($taburl);

echo $output->header();

// Print top tabs.

echo $output->teacherview_tabs($bookking, $taburl, 'datelist');


// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($bookking->cm);
if ($groupmode) {
    $currentgroupid = groups_get_activity_group($bookking->cm, true);

    echo html_writer::start_div('dropdownmenu');
    groups_print_activity_menu($bookking->cm, $taburl);
    echo html_writer::end_div();
}

$scopemenukey = 'scopemenuself';
if (has_capability('mod/bookking:canseeotherteachersbooking', $scopecontext)) {
    $teachers = $bookking->get_available_teachers($currentgroupid);
    $teachermenu = array();
    foreach ($teachers as $teacher) {
        $teachermenu[$teacher->id] = fullname($teacher);
    }
    $select = $output->single_select($taburl, 'teacherid', $teachermenu, $teacherid,
                    array(0 => get_string('myself', 'bookking')), 'teacheridform');
    echo html_writer::div(get_string('teachersmenu', 'bookking', $select), 'dropdownmenu');
    $scopemenukey = 'scopemenu';
}
if (has_capability('mod/bookking:seeoverviewoutsideactivity', $context)) {
    $scopemenu = array('activity' => get_string('thisbookking', 'bookking'),
                    'course' => get_string('thiscourse', 'bookking'),
                    'site' => get_string('thissite', 'bookking'));
    $select = $output->single_select($taburl, 'scope', $scopemenu, $scope, null, 'scopeform');
    echo html_writer::div(get_string($scopemenukey, 'bookking', $select), 'dropdownmenu');
}

// Getting date list.

$params = array();
$params['teacherid']   = $teacherid == 0 ? $USER->id : $teacherid;
$params['courseid']    = $bookking->courseid;
$params['bookkingid'] = $bookking->id;

$scopecond = '';
if ($scope == 'activity') {
    $scopecond = ' AND sc.id = :bookkingid';
} else if ($scope == 'course') {
    $scopecond = ' AND c.id = :courseid';
}

$sql = "SELECT a.id AS id, ".
               user_picture::fields('u1', array('email', 'department'), 'studentid', 'student').", ".
               $DB->sql_fullname('u1.firstname', 'u1.lastname')." AS studentfullname,
               a.appointmentnote,
               a.appointmentnoteformat,
               a.teachernote,
               a.teachernoteformat,
               a.grade,
               sc.name,
               sc.id AS bookkingid,
               sc.scale,
               c.shortname AS courseshort,
               c.id AS courseid, ".
               user_picture::fields('u2', null, 'teacherid').",
               s.id AS sid,
               s.starttime,
               s.duration,
               s.appointmentlocation,
               s.notes,
               s.notesformat
          FROM {course} c,
               {bookking} sc,
               {bookking_appointment} a,
               {bookking_slots} s,
               {user} u1,
               {user} u2
         WHERE c.id = sc.course AND
               sc.id = s.bookkingid AND
               a.slotid = s.id AND
               u1.id = a.studentid AND
               u2.id = s.teacherid AND
               s.teacherid = :teacherid ".
               $scopecond;

$sqlcount =
       "SELECT COUNT(*)
          FROM {course} c,
               {bookking} sc,
               {bookking_appointment} a,
               {bookking_slots} s
         WHERE c.id = sc.course AND
               sc.id = s.bookkingid AND
               a.slotid = s.id AND
               s.teacherid = :teacherid ".
               $scopecond;

$numrecords = $DB->count_records_sql($sqlcount, $params);


$limit = 30;

if ($numrecords) {

    // Make the table of results.

    $coursestr = get_string('course', 'bookking');
    $bookkingstr = get_string('bookking', 'bookking');
    $whenstr = get_string('when', 'bookking');
    $wherestr = get_string('where', 'bookking');
    $whostr = get_string('who', 'bookking');
    $wherefromstr = get_string('department', 'bookking');
    $whatstr = get_string('what', 'bookking');
    $whatresultedstr = get_string('whatresulted', 'bookking');
    $whathappenedstr = get_string('whathappened', 'bookking');

    $tablecolumns = array('courseshort', 'bookkingid', 'starttime', 'appointmentlocation',
                          'studentfullname', 'studentdepartment', 'notes', 'grade', 'appointmentnote');
    $tableheaders = array($coursestr, $bookkingstr, $whenstr, $wherestr,
                          $whostr, $wherefromstr, $whatstr, $whatresultedstr, $whathappenedstr);

    $table = new flexible_table('mod-bookking-datelist');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);

    $table->define_baseurl($taburl);

    $table->sortable(true, 'when'); // Sorted by date by default.
    $table->collapsible(true);      // Allow column hiding.
    $table->initialbars(true);

    $table->column_suppress('courseshort');
    $table->column_suppress('bookkingid');
    $table->column_suppress('starttime');
    $table->column_suppress('studentfullname');
    $table->column_suppress('notes');

    $table->set_attribute('id', 'dates');
    $table->set_attribute('class', 'datelist');

    $table->column_class('course', 'datelist_course');
    $table->column_class('bookking', 'datelist_bookking');

    $table->setup();

    // Get extra query parameters from flexible_table behaviour.
    $where = $table->get_sql_where();
    $sort = $table->get_sql_sort();
    $table->pagesize($limit, $numrecords);

    if (!empty($sort)) {
        $sql .= " ORDER BY $sort";
    }

    $results = $DB->get_records_sql($sql, $params);

    foreach ($results as $id => $row) {
        $courseurl = new moodle_url('/course/view.php', array('id' => $row->courseid));
        $coursedata = html_writer::link($courseurl, format_string($row->courseshort));
        $bookkingurl = new moodle_url('/mod/bookking/view.php', array('a' => $row->bookkingid));
        $bookkingdata = html_writer::link($bookkingurl, format_string($row->name));
        $a = mod_bookking_renderer::slotdatetime($row->starttime, $row->duration);
        $whendata = get_string('slotdatetime', 'bookking', $a);
        $whourl = new moodle_url('/mod/bookking/view.php',
                        array('what' => 'viewstudent', 'a' => $row->bookkingid, 'appointmentid' => $row->id));
        $whodata = html_writer::link($whourl, $row->studentfullname);
        $whatdata = $output->format_notes($row->notes, $row->notesformat, $context, 'slotnote', $row->sid);
        $gradedata = $row->scale == 0 ? '' : $output->format_grade($row->scale, $row->grade);

        $dataset = array(
                        $coursedata,
                        $bookkingdata,
                        $whendata,
                        format_string($row->appointmentlocation),
                        $whodata,
                        $row->studentdepartment,
                        $whatdata,
                        $gradedata,
                        $output->format_appointment_notes($bookking, $row) );
        $table->add_data($dataset);
    }
    $table->print_html();
    echo $output->continue_button($returnurl);
} else {
    notice(get_string('noresults', 'bookking'), $returnurl);
}

echo $output->footer();

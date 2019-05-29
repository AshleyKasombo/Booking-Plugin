<?php


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/excellib.class.php');
require_once($CFG->dirroot.'/lib/odslib.class.php');
require_once($CFG->dirroot.'/lib/csvlib.class.php');
require_once($CFG->dirroot.'/lib/pdflib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');



abstract class bookking_export_field {

    protected $renderer;

    public function set_renderer(mod_bookking_renderer $renderer) {
        $this->renderer = $renderer;
    }

    /**
     * Is the field available in this bookking?
     * @return bool whether the field is available
     */
    public function is_available(bookking_instance $bookking) {
        return true;
    }

    /**
     * Retrieve the unique id (a string) for this field
     */
    public abstract function get_id();

    /**
     * Retrieve the group that this field belongs to -
     * either 'slot' or 'student' or 'appointment',
     *
     * @return string the group id as above
     */
    public abstract function get_group();

    /**
     * Retrieve the header (in the sense of table header in the output)
     * used for this field.
     *
     * @param $bookking the bookking instance in question
     * @return string the header for this field
     */
    public function get_header(bookking_instance $bookking) {
        return get_string('field-'.$this->get_id(), 'bookking');
    }

    /**
     * Retrieve the header (in the sense of table header in the output) as an array.
     * Needs to be overridden for multi-column fields only.
     *
     * @param $bookking the bookking instance in question
     * @return string the header for this field
     */
    public function get_headers(bookking_instance $bookking) {
        return array($this->get_header($bookking));
    }

    /**
     * Retrieve the label used in the configuration form to label this field.
     * By default, this equals the table header.
     *
     * @param $bookking the bookking instance in question
     * @return string the form label for this field
     */
    public function get_formlabel(bookking_instance $bookking) {
        return $this->get_header($bookking);
    }

    /**
     * Retrieves the numer of table columns used by this field (typically 1).
     *
     * @param $bookking the bookking instance in question
     * @return int the number of columns used
     */
    public function get_num_columns(bookking_instance $bookking) {
        return 1;
    }

    /**
     * Retrieve the typical width (in characters) of this field.
     * This is used to set the width of columns in the output, where this is relevant.
     *
     * @param $bookking the bookking instance in question
     * @return int the width of this field (number of characters per column)
     */
    public function get_typical_width(bookking_instance $bookking) {
        return strlen($this->get_formlabel($bookking));
    }

    /**
     * Does this field use wrapped text?
     *
     * @return bool whether wrapping is used for this field
     */
    public function is_wrapping() {
        return false;
    }

    /**
     * Retrieve the value of this field in a particular data record
     *
     * @param $slot the bookking slot to get data from
     * @param $appointment the appointment to evaluate (may be null for an empty slot)
     * @return string the value of this field for the given data
     */
    public abstract function get_value(bookking_slot $slot, $appointment);

    /**
     * Retrieve the value of this field as an array.
     * Needs to be overriden for multi-column fields only.
     *
     * @param $slot the bookking slot to get data from
     * @param $appointment the appointment to evaluate (may be null for an empty slot)
     * @return array an array of strings containing the column values
     */
    public function get_values(bookking_slot $slot, $appointment) {
        return array($this->get_value($slot, $appointment));
    }

}


/**
 * Get a list of all export fields available.
 *
 * @return array the fields as an array of bookking_export_field objects.
 */
function bookking_get_export_fields(bookking_instance $bookking) {
    $result = array();
    $result[] = new bookking_slotdate_field();
    $result[] = new bookking_starttime_field();
    $result[] = new bookking_endtime_field();
    $result[] = new bookking_location_field();
    $result[] = new bookking_teachername_field();
    $result[] = new bookking_maxstudents_field();
    $result[] = new bookking_slotnotes_field();

    $result[] = new bookking_student_field('studentfullname', 'fullname', 25);
    $result[] = new bookking_student_field('studentfirstname', 'firstname');
    $result[] = new bookking_student_field('studentlastname', 'lastname');
    $result[] = new bookking_student_field('studentemail', 'email', 0, true);
    $result[] = new bookking_student_field('studentusername', 'username');
    $result[] = new bookking_student_field('studentidnumber', 'idnumber', 0, true);

    $pfields = profile_get_custom_fields();
    foreach ($pfields as $id => $field) {
        $type = $field->datatype;
        $result[] = new bookking_profile_field('profile_'.$type, $id, $type);
    }

    $result[] = new bookking_groups_single_field();
    $result[] = new bookking_groups_multi_field($bookking);

    $result[] = new bookking_attended_field();
    $result[] = new bookking_grade_field();
    $result[] = new bookking_appointmentnote_field();
    $result[] = new bookking_teachernote_field();
    $result[] = new bookking_studentnote_field();
    $result[] = new bookking_filecount_field();

    return $result;
}


/**
 * Export field: Date of the slot
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_slotdate_field extends bookking_export_field {

    public function get_id() {
        return 'date';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_typical_width(bookking_instance $bookking) {
        return strlen(mod_bookking_renderer::userdate(1)) + 3;
    }

    public function get_value(bookking_slot $slot, $appointment) {
        return mod_bookking_renderer::userdate($slot->starttime);
    }
}

/**
 * Export field: Start time of the slot
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_starttime_field extends bookking_export_field {

    public function get_id() {
        return 'starttime';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(bookking_slot $slot, $appointment) {
        return mod_bookking_renderer::usertime($slot->starttime);
    }

}


/**
 * Export field: End time of the slot
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_endtime_field extends bookking_export_field {

    public function get_id() {
        return 'endtime';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(bookking_slot $slot, $appointment) {
        return mod_bookking_renderer::usertime($slot->endtime);
    }

}

/**
 * Export field: Full name of the teacher
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_teachername_field extends bookking_export_field {

    public function get_id() {
        return 'teachername';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_header(bookking_instance $bookking) {
        return $bookking->get_teacher_name();
    }

    public function get_typical_width(bookking_instance $bookking) {
        return 20;
    }

    public function get_value(bookking_slot $slot, $appointment) {
        return fullname($slot->teacher);
    }

}

/**
 * Export field: Appointment location
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_location_field extends bookking_export_field {

    public function get_id() {
        return 'location';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(bookking_slot $slot, $appointment) {
        return format_string($slot->appointmentlocation);
    }

}

/**
 * Export field: Maximum number of students / appointments in the slot
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_maxstudents_field extends bookking_export_field {

    public function get_id() {
        return 'maxstudents';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if ($slot->exclusivity <= 0) {
            return get_string('unlimited', 'bookking');
        } else {
            return $slot->exclusivity;
        }
    }

}

/**
 * Export field: A field in the student record (to be chosen via the constructor)
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_student_field extends bookking_export_field {

    protected $id;
    protected $studfield;
    protected $typicalwidth;
    protected $idfield;

    public function __construct($id, $studfield, $typicalwidth = 0, $idfield = false) {
        $this->id = $id;
        $this->studfield = $studfield;
        $this->typicalwidth = $typicalwidth;
        $this->idfield = $idfield;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_group() {
        return 'student';
    }

    public function is_available(bookking_instance $bookking) {
        if (!$this->idfield) {
            return true;
        }
        $ctx = $bookking->get_context();
        return has_capability('moodle/site:viewuseridentity', $ctx);
    }

    public function get_typical_width(bookking_instance $bookking) {
        if ($this->typicalwidth > 0) {
            return $this->typicalwidth;
        } else {
            return parent::get_typical_width($bookking);
        }
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        $student = $appointment->get_student();
        if (is_null($student)) {
            return '';
        }
        if ($this->studfield == 'fullname') {
            return fullname($student);
        } else {
            return $student->{$this->studfield};
        }
    }

}

/**
 * Export field: A cutom profile field in the student record
 *
 * @package    mod_bookking
 * @copyright  2017 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_profile_field extends bookking_export_field {

    protected $id;
    protected $field;

    /**
     * Create a new export entry for a custom profile field.
     *
     * @param string $id the id of the field (for internal use)
     * @param int $fieldid id of the field in the database table
     * @param string $type data type of profile field to add
     */
    public function __construct($id, $fieldid, $type) {
        global $CFG;

        $this->id = $id;
        require_once($CFG->dirroot.'/user/profile/field/'.$type.'/field.class.php');
        $fieldclass = 'profile_field_'.$type;
        $fieldobj = new $fieldclass($fieldid, 0);
        $this->field = $fieldobj;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_group() {
        return 'student';
    }

    public function is_available(bookking_instance $bookking) {
        return $this->field->is_visible();
    }

    public function get_header(bookking_instance $bookking) {
        return format_string($this->field->field->name);
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (!$appointment instanceof bookking_appointment || $appointment->studentid == 0) {
            return '';
        }
        $this->field->set_userid($appointment->studentid);
        $this->field->load_data();
        if ($this->field->is_visible()) {
            $content = $this->field->display_data();
            return strip_tags($content);
        }
        return '';
    }

}


/**
 * Export field: Whether the appointment has been attended
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_attended_field extends bookking_export_field {

    public function get_id() {
        return 'attended';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        $str = $appointment->is_attended() ? get_string('yes') : get_string('no');
        return $str;
    }

}

/**
 * Export field: Slot notes
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_slotnotes_field extends bookking_export_field {

    public function get_id() {
        return 'slotnotes';
    }

    public function get_group() {
        return 'slot';
    }

    public function get_typical_width(bookking_instance $bookking) {
        return 30;
    }

    public function is_wrapping() {
        return true;
    }

    public function get_value(bookking_slot $slot, $appointment) {
        return strip_tags($slot->notes);
    }

}

/**
 * Export field: Appointment notes
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_appointmentnote_field extends bookking_export_field {

    public function get_id() {
        return 'appointmentnote';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_typical_width(bookking_instance $bookking) {
        return 30;
    }

    public function is_wrapping() {
        return true;
    }

    public function is_available(bookking_instance $bookking) {
        return $bookking->uses_appointmentnotes();
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        return strip_tags($appointment->appointmentnote);
    }

}

/**
 * Export field: Teacher notes
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_teachernote_field extends bookking_export_field {

    public function get_id() {
        return 'teachernote';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_typical_width(bookking_instance $bookking) {
        return 30;
    }

    public function is_wrapping() {
        return true;
    }

    public function is_available(bookking_instance $bookking) {
        return $bookking->uses_teachernotes();
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        return strip_tags($appointment->teachernote);
    }

}

/**
 * Export field: Student-provided notes
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_studentnote_field extends bookking_export_field {

    public function get_id() {
        return 'studentnote';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_typical_width(bookking_instance $bookking) {
        return 30;
    }

    public function is_wrapping() {
        return true;
    }

    public function is_available(bookking_instance $bookking) {
        return $bookking->uses_studentnotes();
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        return strip_tags($appointment->studentnote);
    }

}

/**
 * Export field: Number of student-provided files
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_filecount_field extends bookking_export_field {

    public function get_id() {
        return 'filecount';
    }

    public function get_group() {
        return 'appointment';
    }

    public function get_typical_width(bookking_instance $bookking) {
        return 2;
    }

    public function is_wrapping() {
        return false;
    }

    public function is_available(bookking_instance $bookking) {
        return $bookking->uses_studentfiles();
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        return $appointment->count_studentfiles();
    }

}

/**
 * Export field: Grade for the appointment
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_grade_field extends bookking_export_field {

    public function get_id() {
        return 'grade';
    }

    public function get_group() {
        return 'appointment';
    }

    public function is_available(bookking_instance $bookking) {
        return $bookking->uses_grades();
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        return $this->renderer->format_grade($slot->get_bookking(), $appointment->grade);
    }

}

/**
 * Export field: Student groups (in one column)
 *
 * @package    mod_bookking
 * @copyright  2018 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_groups_single_field extends bookking_export_field {

    public function get_id() {
        return 'groupssingle';
    }

    public function get_group() {
        return 'student';
    }

    public function is_available(bookking_instance $bookking) {
        $g = groups_get_all_groups($bookking->courseid, 0, $bookking->get_cm()->groupingid);
        return count($g) > 0;
    }

    public function get_formlabel(bookking_instance $bookking) {
        return get_string('field-groupssingle-label', 'bookking');
    }

    public function get_value(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        $bookking = $slot->get_bookking();
        $groups = groups_get_user_groups($bookking->courseid, $appointment->studentid);
        $groupingid = $bookking->get_cm()->groupingid;
        $gn = array();
        foreach ($groups[$groupingid] as $groupid) {
            $gn[] = groups_get_group_name($groupid);
        }
        return implode(',', $gn);
    }

}

/**
 * Export field: Student groups (in several columns)
 *
 * @package    mod_bookking
 * @copyright  2018 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_groups_multi_field extends bookking_export_field {

    protected $coursegroups;

    public function __construct(bookking_instance $bookking) {
        $this->coursegroups =  groups_get_all_groups($bookking->courseid, 0, $bookking->get_cm()->groupingid);
    }

    public function get_id() {
        return 'groupsmulti';
    }

    public function get_group() {
        return 'student';
    }

    public function is_available(bookking_instance $bookking) {
        return count($this->coursegroups) > 0;
    }

    public function get_num_columns(bookking_instance $bookking) {
        return count($this->coursegroups);
    }

    public function get_headers(bookking_instance $bookking) {
        $result = array();
        foreach ($this->coursegroups as $group) {
            $result[] = $group->name;
        }
        return $result;
    }

    public function get_value(bookking_slot $slot, $appointment) {
        return '';
    }

    public function get_values(bookking_slot $slot, $appointment) {
        if (! $appointment instanceof bookking_appointment) {
            return '';
        }
        $usergroups = groups_get_user_groups($slot->get_bookking()->courseid, $appointment->studentid)[0];
        $result = array();
        foreach ($this->coursegroups as $group) {
            $key = in_array($group->id, $usergroups) ? 'yes' : 'no';
            $result[] = get_string($key);
        }
        return $result;
    }

}




/**
 * An "output device" for bookking exports
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class bookking_canvas {


    /**
     * @var object format instructions for header
     */
    public $formatheader;

    /**
     * @var object format instructions for boldface text
     */
    public $formatbold;

    /**
     * @var object format instructions for boldface italic text
     */
    public $formatboldit;

    /**
     * @var object format instructions for text with line wrapping
     */
    public $formatwrap;

    /**
     * Start a new page (tab, etc.) with an optional title.
     *
     * @param $title the title of the page
     */
    public abstract function start_page($title);

    /**
     * Write a string into a certain position of the canvas.
     *
     * @param $row the row into which to write (starts with 0)
     * @param $column the column into which to write (starts with 0)
     * @param $str the string to write
     * @param $format the format to use (one of the $format... fields of this object), can be null
     */
    public abstract function write_string($row, $col, $str, $format);

    /**
     * Write a number into a certain position of the canvas.
     *
     * @param $row the row into which to write (starts with 0)
     * @param $column the column into which to write (starts with 0)
     * @param $num the number to write
     * @param $format the format to use (one of the $format... fields of this object), can be null
     */
    public abstract function write_number($row, $col, $num, $format);

    /**
     * Merge a range of cells in the same row.
     *
     * @param $row the row in which to merge
     * @param $fromcol the first column to merge
     * @param $tocol the last column to merge
     */
    public abstract function merge_cells($row, $fromcol, $tocol);

    /**
     * Set the width of a particular column. (This will make sense only for certain outout formats,
     * it can be ignored otherwise.)
     *
     * @param $col the affected column
     * @param $width the width of that column
     */
    public function set_column_width($col, $width) {
        // Ignore widths by default.
    }

    /**
     * @var string title of the output file
     */
    protected $title;

    /**
     * Set the title of the entire output file.
     *
     * This is stored in the field $title, and can be used as appropriate for the particular implementation.
     *
     * @param title the title to set
     */
    public function set_title($title) {
        $this->title = $title;
    }

    /**
     * Send the output file via HTTP, as a downloadable file.
     *
     * @param $filename the file name to send
     */
    public abstract function send($filename);

}



/**
 * Output device: Excel file
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_excel_canvas extends bookking_canvas {

    protected $workbook;
    protected $worksheet;


    public function __construct() {

        // Create a workbook.
        $this->workbook = new MoodleExcelWorkbook("-");

        // Set up formats.
        $this->formatheader = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatboldit = $this->workbook->add_format();
        $this->formatwrap = $this->workbook->add_format();
        $this->formatheader->set_bold();
        $this->formatbold->set_bold();
        $this->formatboldit->set_bold();
        $this->formatboldit->set_italic();
        $this->formatwrap->set_text_wrap();

    }


    public function start_page($title) {
        $this->worksheet = $this->workbook->add_worksheet($title);
    }

    private function ensure_open_page() {
        if (!$this->worksheet) {
            $this->start_page('');
        }
    }

    public function write_string($row, $col, $str, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_string($row, $col, $str, $format);
    }

    public function write_number($row, $col, $num, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_number($row, $col, $num, $format);
    }

    public function merge_cells($row, $fromcol, $tocol) {
        $this->ensure_open_page();
        $this->worksheet->merge_cells($row, $fromcol, $row, $tocol);
    }

    public function set_column_width($col, $width) {
        $this->worksheet->set_column($col, $col, $width);
    }

    public function send($filename) {
        $this->workbook->send($filename);
        $this->workbook->close();
    }

}

/**
 * Output device: ODS file
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_ods_canvas extends bookking_canvas {

    protected $workbook;
    protected $worksheet;


    public function __construct() {

        // Create a workbook.
        $this->workbook = new MoodleODSWorkbook("-");

        // Set up formats.
        $this->formatheader = $this->workbook->add_format();
        $this->formatbold = $this->workbook->add_format();
        $this->formatboldit = $this->workbook->add_format();
        $this->formatwrap = $this->workbook->add_format();
        $this->formatheader->set_bold();
        $this->formatbold->set_bold();
        $this->formatboldit->set_bold();
        $this->formatboldit->set_italic();
        $this->formatwrap->set_text_wrap();

    }


    public function start_page($title) {
        $this->worksheet = $this->workbook->add_worksheet($title);
    }

    private function ensure_open_page() {
        if (!$this->worksheet) {
            $this->start_page('');
        }
    }


    public function write_string($row, $col, $str, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_string($row, $col, $str, $format);
    }

    public function write_number($row, $col, $num, $format=null) {
        $this->ensure_open_page();
        $this->worksheet->write_number($row, $col, $num, $format);
    }

    public function merge_cells($row, $fromcol, $tocol) {
        $this->ensure_open_page();
        $this->worksheet->merge_cells($row, $fromcol, $row, $tocol);
    }

    public function set_column_width($col, $width) {
        $this->worksheet->set_column($col, $col, $width);
    }

    public function send($filename) {
        $this->workbook->send($filename);
        $this->workbook->close();
    }

}


/**
 * An output device that is based on first collecting all text in an array.
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class bookking_cached_text_canvas extends bookking_canvas {

    protected $pages;
    protected $curpage;

    public function __construct() {

        $this->formatheader = 'header';
        $this->formatbold = 'bold';
        $this->formatboldit = 'boldit';
        $this->formatwrap = 'wrap';

        $this->start_page('');

    }

    protected function get_col_count($page) {
        $maxcol = 0;
        foreach ($page->cells as $rownum => $row) {
            foreach ($row as $colnum => $col) {
                if ($colnum > $maxcol) {
                    $maxcol = $colnum;
                }
            }
        }
        return $maxcol + 1;
    }

    protected function get_row_count($page) {
        $maxrow = 0;
        foreach ($page->cells as $rownum => $row) {
            if ($rownum > $maxrow) {
                $maxrow = $rownum;
            }
        }
        return $maxrow + 1;
    }

    protected function compute_relative_widths($page) {
        $cols = $this->get_col_count($page);
        $sum = 0;
        foreach ($page->columnwidths as $width) {
            $sum += $width;
        }
        $relwidths = array();
        for ($col = 0; $col < $cols; $col++) {
            if ($sum > 0 && isset($page->columnwidths[$col])) {
                $relwidths[$col] = (int) ($page->columnwidths[$col] / $sum * 100);
            } else {
                $relwidths[$col] = 0;
            }
        }
        return $relwidths;
    }

    public function start_page($title) {
        $onemptypage = $this->curpage &&  !$this->curpage->cells && !$this->curpage->mergers && !$this->curpage->title;
        if ($onemptypage) {
            $this->curpage->title = $title;
        } else {
            $newpage = new stdClass;
            $newpage->title = $title;
            $newpage->cells = array();
            $newpage->formats = array();
            $newpage->mergers = array();
            $newpage->columnwidths = array();
            $this->pages[] = $newpage;
            $this->curpage = $newpage;
        }
    }


    public function write_string($row, $col, $str, $format=null) {
        $this->curpage->cells[$row][$col] = $str;
        $this->curpage->formats[$row][$col] = $format;
    }

    public function write_number($row, $col, $num, $format=null) {
        $this->write_string($row, $col, $num, $format);
    }

    public function merge_cells($row, $fromcol, $tocol) {
        $this->curpage->mergers[$row][$fromcol] = $tocol - $fromcol + 1;
    }

    public function set_column_width($col, $width) {
        $this->curpage->columnwidths[$col] = $width;
    }

}

/**
 * Output device: HTML file
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_html_canvas extends bookking_cached_text_canvas {

    public function as_html($rowcutoff, $usetitle = true) {
        global $OUTPUT;

        $o = '';

        if ($usetitle && $this->title) {
            $o .= html_writer::tag('h1', $this->title);
        }

        foreach ($this->pages as $page) {
            if ($page->title) {
                $o .= html_writer::tag('h2', $page->title);
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);
            if ($rowcutoff && $rows > $rowcutoff) {
                $rows = $rowcutoff;
            }
            $relwidths = $this->compute_relative_widths($page);

            $table = new html_table();
            $table->cellpadding = 3;
            for ($row = 0; $row < $rows; $row++) {
                $hrow = new html_table_row();
                $col = 0;
                while ($col < $cols) {
                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $cell = new html_table_cell('');
                    $text = '';
                    if (isset($page->cells[$row][$col])) {
                        $text = $page->cells[$row][$col];
                    }
                    if (isset($page->formats[$row][$col])) {
                        $cell->header = ($page->formats[$row][$col] == 'header');
                        if ($page->formats[$row][$col] == 'boldit') {
                            $text = html_writer::tag('i', $text);
                            $text = html_writer::tag('b', $text);
                        }
                        if ($page->formats[$row][$col] == 'bold') {
                            $text = html_writer::tag('b', $text);
                        }
                    }
                    if ($span > 1) {
                        $cell->colspan = $span;
                    }
                    if ($row == 0 & $relwidths[$col] > 0) {
                        $cell->width = $relwidths[$col].'%';
                    }
                    $cell->text = $text;
                    $hrow->cells[] = $cell;
                    $col = $col + $span;
                }
                $table->data[] = $hrow;
            }
            $o .= html_writer::table($table);
        }
        return $o;
    }

    public function send($filename) {
        global $OUTPUT, $PAGE;
        $PAGE->set_pagelayout('print');
        echo $OUTPUT->header();
        echo $this->as_html(0, true);
        echo $OUTPUT->footer();
    }

}

/**
 * Output device: CSV (text) file
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_csv_canvas extends bookking_cached_text_canvas {

    protected $delimiter;

    public function __construct($delimiter) {
        parent::__construct();
        $this->delimiter = $delimiter;
    }

    public function send($filename) {

        $writer = new csv_export_writer($this->delimiter);
        $writer->set_filename($filename);

        foreach ($this->pages as $page) {
            if ($page->title) {
                $writer->add_data(array('*** '.$page->title.' ***'));
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);

            for ($row = 0; $row < $rows; $row++) {
                $data = array();
                $col = 0;
                while ($col < $cols) {
                    if (isset($page->cells[$row][$col])) {
                        $data[] = $page->cells[$row][$col];
                    } else {
                        $data[] = '';
                    }

                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $col += $span;
                }
                $writer->add_data($data);
            }
        }

        $writer->download_file();
    }

}

/**
 * Output device: PDF file
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_pdf_canvas extends bookking_cached_text_canvas {

    protected $orientation;

    public function __construct($orientation) {
        parent::__construct();
        $this->orientation = $orientation;
    }

    public function send($filename) {

        $doc = new pdf($this->orientation);
        if ($this->title) {
            $doc->setHeaderData('', 0, $this->title);
            $doc->setPrintHeader(true);
        } else {
            $doc->setPrintHeader(false);
        }
        $doc->setPrintFooter(false);

        foreach ($this->pages as $page) {
            $doc->AddPage();
            if ($page->title) {
                $doc->writeHtml('<h2>'.$page->title.'</h2>');
            }

            // Find extent of the table.
            $rows = $this->get_row_count($page);
            $cols = $this->get_col_count($page);
            $relwidths = $this->compute_relative_widths($page);

            $o = html_writer::start_tag('table', array('border' => 1, 'cellpadding' => 1));
            for ($row = 0; $row < $rows; $row++) {
                $o .= html_writer::start_tag('tr');
                $col = 0;
                while ($col < $cols) {
                    $span = 1;
                    if (isset($page->mergers[$row][$col])) {
                        $mergewidth = (int) $page->mergers[$row][$col];
                        if ($mergewidth >= 1) {
                            $span = $mergewidth;
                        }
                    }
                    $opts = array();
                    if ($row == 0 && $relwidths[$col] > 0) {
                        $opts['width'] = $relwidths[$col].'%';
                    }
                    if ($span > 1) {
                        $opts['colspan'] = $span;
                    }
                    $o .= html_writer::start_tag('td', $opts);
                    $cell = '';
                    if (isset($page->cells[$row][$col])) {
                        $cell = s($page->cells[$row][$col]);
                        if (isset($page->formats[$row][$col])) {
                            $thisformat = $page->formats[$row][$col];
                            if ($thisformat == 'header') {
                                $cell = html_writer::tag('b', $cell);
                            } else if ($thisformat == 'boldit') {
                                $cell = html_writer::tag('i', $cell);
                            }
                        }
                    }
                    $o .= $cell;

                    $o .= html_writer::end_tag('td');

                    $col += $span;
                }
                $o .= html_writer::end_tag('tr');
            }
            $o .= html_Writer::end_tag('table');
            $doc->writeHtml($o);
        }

        $doc->Output($filename.'.pdf');
    }

}

/**
 * A class that generates the export file with given settings.
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_export {

    /**
     * @var bookking_canvas the canvas used for output
     */
    protected $canvas;

    /**
     * @var array a list of student ids to be filtered for
     */
    protected $studfilter = null;

    /**
     * Create a new export with a given canvas
     *
     * @param bookking_canvas $canvas the canvas to use
     */
    public function __construct(bookking_canvas $canvas) {
        $this->canvas = $canvas;
    }

    /**
     * Build the output on the canvas.
     *
     * @param bookking_instance $bookking the bookking to export
     * @param array $fields the fields to include
     * @param string $mode output mode
     * @param int $userid id of the teacher to export for, 0 if slots for all teachers are exported
     * @param int $groupid the id of the group (of students) to export appointments for, 0 if none
     * @param bool $includeempty whether to include slots without appointments
     * @param bool $pageperteacher whether one page should be used for each teacher
     */
    public function build(bookking_instance $bookking, array $fields, $mode, $userid, $groupid, $includeempty, $pageperteacher) {
        if ($groupid) {
            $this->studfilter = array_keys(groups_get_members($groupid, 'u.id'));
        }
        $this->canvas->set_title(format_string($bookking->name));
        if ($userid) {
            $slots = $bookking->get_slots_for_teacher($userid, $groupid);
            $this->build_page($bookking, $fields, $slots, $mode, $includeempty);
        } else if ($pageperteacher) {
            $teachers = $bookking->get_teachers();
            foreach ($teachers as $teacher) {
                $slots = $bookking->get_slots_for_teacher($teacher->id, $groupid);
                $title = fullname($teacher);
                $this->canvas->start_page($title);
                $this->build_page($bookking, $fields, $slots, $mode, $includeempty);
            }
        } else {
            $slots = $bookking->get_slots_for_group($groupid);
            $this->build_page($bookking, $fields, $slots, $mode, $includeempty);
        }
    }

    /**
     * Write a page of output to the canvas.
     * (Pages correspond to "tabs" in spreadsheet format, not to printed pages.)
     *
     * @param bookking_instance $bookking the bookking being exported
     * @param array $fields the fields to include
     * @param array $slots the slots to include
     * @param string $mode output mode
     * @param bool $includeempty whether to include slots without appointments
     */
    protected function build_page(bookking_instance $bookking, array $fields, array $slots, $mode, $includeempty) {

        // Output the header.
        $row = 0;
        $col = 0;
        foreach ($fields as $field) {
            if ($field->get_group() != 'slot' || $mode != 'appointmentsgrouped') {
                $headers = $field->get_headers($bookking);
                $numcols = $field->get_num_columns($bookking);
                for ($i = 0; $i < $numcols; $i++) {
                    $this->canvas->write_string($row, $col + $i, $headers[$i], $this->canvas->formatheader);
                    $this->canvas->set_column_width($col + $i, $field->get_typical_width($bookking));
                }
                $col = $col + $numcols;
            }
        }
        $row++;

        // Output the data rows.
        foreach ($slots as $slot) {
            $appts = $slot->get_appointments($this->studfilter);
            if ($mode == 'appointmentsgrouped') {
                if ($appts || $includeempty) {
                    $this->write_row_summary($row, $slot, $fields);
                    $row++;
                }
                foreach ($appts as $appt) {
                    $this->write_row($row, $slot, $appt, $fields, false);
                    $row++;
                }
            } else {
                if ($appts) {
                    if ($mode == 'onelineperappointment') {
                        foreach ($appts as $appt) {
                            $this->write_row($row, $slot, $appt, $fields, true);
                            $row++;
                        }
                    } else {
                        $this->write_row($row, $slot, $appts[0], $fields, true, count($appts) > 1);
                        $row++;
                    }
                } else if ($includeempty) {
                    $this->write_row($row, $slot, null, $fields, true);
                    $row++;
                }
            }
        }

    }

    /**
     * Write a row of the export to the canvas
     * @param int $row row number on canvas
     * @param bookking_slot $slot the slot of the appointment to write
     * @param bookking_appointment $appointment the appointment to write
     * @param array $fields list of fields to include
     * @param bool $includeslotfields whether fields relating to slots, rather than appointments, should be included
     * @param string $multiple whether the row represents multiple values (appointments)
     */
    protected function write_row($row, bookking_slot $slot, $appointment, array $fields, $includeslotfields = true, $multiple = false) {

        $col = 0;
        foreach ($fields as $field) {
            if ($includeslotfields || $field->get_group() != 'slot') {
                if ($multiple && $field->get_group() != 'slot') {
                    $value = get_string('multiple', 'bookking');
                    $this->canvas->write_string($row, $col, $value);
                    $col++;
                } else {
                    $numcols = $field->get_num_columns($slot->get_bookking());
                    $values = $field->get_values($slot, $appointment);
                    $format = $field->is_wrapping() ? $this->canvas->formatwrap : null;
                    for ($i = 0; $i < $numcols; $i++) {
                        $this->canvas->write_string($row, $col + $i, $values[$i], $format);
                    }
                    $col = $col + $numcols;
                }
            }
        }
    }

    /**
     * Write a summary of slot-related data into a row
     *
     * @param int $row the row number on the canvas
     * @param bookking_slot $slot the slot to be written
     * @param array $fields the fields to include
     */
    protected function write_row_summary($row, bookking_slot $slot, array $fields) {

        $strs = array();
        $cols = 0;
        foreach ($fields as $field) {
            if ($field->get_group() == 'slot') {
                $strs[] = $field->get_value($slot, null);
            } else {
                $cols++;
            }
        }
        $str = implode(' - ', $strs);
        $this->canvas->write_string($row, 0, $str, $this->canvas->formatboldit);
        $this->canvas->merge_cells($row, 0, $cols - 1);
    }

}

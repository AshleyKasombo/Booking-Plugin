<?php

/**
 * Appointment-related forms of the bookking module
 * (using Moodle formslib)
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Form to edit one appointment
 *
 * @package    mod_bookking
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookking_editappointment_form extends moodleform {

    /**
     * @var bookking_appointment the appointment being edited
     */
    protected $appointment;

   /**
     * @var bool whether to distribute grade to all group members
     */
    protected $distribute;

    /**
     * @var whether the teacher can edit grades
     */
    protected $editgrade;

    /**
     * @var array options for notes fields
     */
    public $noteoptions;

    /**
     * Create a new edit appointment form
     *
     * @param bookking_appointment $appointment the appointment to edit
     * @param mixed $action the action attribute for the form
     * @param bool $editgrade whether the grade can be edited
     * @param bool $distribute whether to distribute grades to all group members
     */
    public function __construct(bookking_appointment $appointment, $action, $editgrade, $distribute) {
        $this->appointment = $appointment;
        $this->distribute = $distribute;
        $this->editgrade = $editgrade;
        $this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $appointment->get_bookking()->get_context(),
                                   'subdirs' => false, 'collapsed' => true);
        parent::__construct($action, null);
    }

    protected function definition() {

        global $output;

        $mform = $this->_form;
        $bookking = $this->appointment->get_bookking();

        // Seen tickbox.
        $mform->addElement('checkbox', 'attended', get_string('attended', 'bookking'));

        // Grade.
        if ($bookking->scale != 0) {
            if ($this->editgrade) {
                $gradechoices = $output->grading_choices($bookking);
                $mform->addElement('select', 'grade', get_string('grade', 'bookking'), $gradechoices);
            } else {
                $gradetext = $output->format_grade($bookking, $this->appointment->grade);
                $mform->addElement('static', 'gradedisplay', get_string('grade', 'bookking'), $gradetext);
            }
        }
        // Appointment notes (visible to teacher and/or student).
        if ($bookking->uses_appointmentnotes()) {
            $mform->addElement('editor', 'appointmentnote_editor', get_string('appointmentnote', 'bookking'),
                               array('rows' => 3, 'columns' => 60), $this->noteoptions);
            $mform->setType('appointmentnote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        }
        if ($bookking->uses_teachernotes()) {
            $mform->addElement('editor', 'teachernote_editor', get_string('teachernote', 'bookking'),
                               array('rows' => 3, 'columns' => 60), $this->noteoptions);
            $mform->setType('teachernote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        }
        if ($this->distribute && ($bookking->uses_appointmentnotes() || $bookking->uses_teachernotes() || $this->editgrade) ) {
            $mform->addElement('checkbox', 'distribute', get_string('distributetoslot', 'bookking'));
            $mform->setDefault('distribute', false);
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    /**
     * Prepare form data from an appointment record
     *
     * @param bookking_appointment $appointment appointment to edit
     * @return stdClass form data
     */
    public function prepare_appointment_data(bookking_appointment $appointment) {
        $newdata = clone($appointment->get_data());
        $context = $this->appointment->get_bookking()->get_context();

        $newdata = file_prepare_standard_editor($newdata, 'appointmentnote', $this->noteoptions, $context,
                                                'mod_bookking', 'appointmentnote', $this->appointment->id);

        $newdata = file_prepare_standard_editor($newdata, 'teachernote', $this->noteoptions, $context,
                                                'mod_bookking', 'teachernote', $this->appointment->id);
        return $newdata;
    }

    /**
     * Save form data into appointment record
     *
     * @param stdClass $formdata data extracted from form
     * @param bookking_appointment $appointment appointment to update
     */
    public function save_appointment_data(stdClass $formdata, bookking_appointment $appointment) {
        $bookking = $appointment->get_bookking();
        $cid = $bookking->context->id;
        $appointment->set_data($formdata);
        $appointment->attended = isset($formdata->attended);
        if ($bookking->uses_appointmentnotes() && isset($formdata->appointmentnote_editor)) {
            $editor = $formdata->appointmentnote_editor;
            $appointment->appointmentnote = file_save_draft_area_files($editor['itemid'], $cid,
                    'mod_bookking', 'appointmentnote', $appointment->id,
                    $this->noteoptions, $editor['text']);
            $appointment->appointmentnoteformat = $editor['format'];
        }
        if ($bookking->uses_teachernotes() && isset($formdata->teachernote_editor)) {
            $editor = $formdata->teachernote_editor;
            $appointment->teachernote = file_save_draft_area_files($editor['itemid'], $cid,
                    'mod_bookking', 'teachernote', $appointment->id,
                    $this->noteoptions, $editor['text']);
            $appointment->teachernoteformat = $editor['format'];
        }
        $appointment->save();
        if (isset($formdata->distribute)) {
            $slot = $appointment->get_slot();
            $slot->distribute_appointment_data($appointment);
        }
    }
}


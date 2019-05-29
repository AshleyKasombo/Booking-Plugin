<?php


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/bookking/exportlib.php');


class bookking_export_form extends moodleform {

    /**
     * @var bookking_instance the bookking to be exported
     */
    protected $bookking;

    /**
     * Create a new export settings form.
     *
     * @param string $action
     * @param bookking_instance $bookking the bookking to export
     * @param object $customdata
     */
    public function __construct($action, bookking_instance $bookking, $customdata=null) {
        $this->bookking = $bookking;
        parent::__construct($action, $customdata);
    }

    protected function definition() {

        $mform = $this->_form;

        // General introduction.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $radios = array();
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('onelineperslot', 'bookking'), 'onelineperslot');
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('onelineperappointment', 'bookking'),  'onelineperappointment');
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('appointmentsgrouped', 'bookking'), 'appointmentsgrouped');
        $mform->addGroup($radios, 'contentgroup',
                                          get_string('contentformat', 'bookking'), null, false);
        $mform->setDefault('content', 'onelineperappointment');
        $mform->addHelpButton('contentgroup', 'contentformat', 'bookking');

        if (has_capability('mod/bookking:canseeotherteachersbooking', $this->bookking->get_context())) {
            $selopt = array('me' => get_string('myself', 'bookking'),
                'all' => get_string ('everyone', 'bookking'));
            $mform->addElement('select', 'includewhom', get_string('includeslotsfor', 'bookking'), $selopt);
            $mform->setDefault('includewhom', 'all');

            $selopt = array('all' => get_string('allononepage', 'bookking'),
                'perteacher' => get_string('pageperteacher', 'bookking', $this->bookking->get_teacher_name()) );
            $mform->addElement('select', 'paging', get_string('pagination', 'bookking'),  $selopt);
            $mform->addHelpButton('paging', 'pagination', 'bookking');

        }

        $mform->addElement('selectyesno', 'includeemptyslots', get_string('includeemptyslots', 'bookking'));
        $mform->setDefault('includeemptyslots', 1);

        // Select data to export.
        $mform->addElement('header', 'datafieldhdr', get_string('datatoinclude', 'bookking'));
        $mform->addHelpButton('datafieldhdr', 'datatoinclude', 'bookking');

        $this->add_exportfield_group('slot', 'slot');
        $this->add_exportfield_group('student', 'student');
        $this->add_exportfield_group('appointment', 'appointment');

        $mform->setDefault('field-date', 1);
        $mform->setDefault('field-starttime', 1);
        $mform->setDefault('field-endtime', 1);
        $mform->setDefault('field-teachername', 1);
        $mform->setDefault('field-studentfullname', 1);
        $mform->setDefault('field-attended', 1);

        // Output file format.
        $mform->addElement('header', 'fileformathdr', get_string('fileformat', 'bookking'));
        $mform->addHelpButton('fileformathdr', 'fileformat', 'bookking');

        $radios = array();
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('csvformat', 'bookking'), 'csv');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('excelformat', 'bookking'),  'xls');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('odsformat', 'bookking'), 'ods');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('htmlformat', 'bookking'), 'html');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('pdfformat', 'bookking'), 'pdf');
        $mform->addGroup($radios, 'outputformatgroup', get_string('fileformat', 'bookking'), null, false);
        $mform->setDefault('outputformat', 'csv');

        $selopt = array('comma'     => get_string('sepcomma', 'bookking'),
                        'colon'     => get_string('sepcolon', 'bookking'),
                        'semicolon' => get_string('sepsemicolon', 'bookking'),
                        'tab'       => get_string('septab', 'bookking'));
        $mform->addElement('select', 'csvseparator', get_string('csvfieldseparator', 'bookking'),  $selopt);
        $mform->setDefault('csvseparator', 'comma');
        $mform->disabledIf('csvseparator', 'outputformat', 'neq', 'csv');

        $selopt = array('P' => get_string('portrait', 'bookking'),
                        'L' => get_string('landscape', 'bookking'));
        $mform->addElement('select', 'pdforientation', get_string('pdforientation', 'bookking'),  $selopt);
        $mform->disabledIf('pdforientation', 'outputformat', 'neq', 'pdf');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'preview', get_string('preview', 'bookking'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('createexport', 'bookking'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }

    /**
     * Add a group of export fields to the form.
     *
     * @param string $groupid id of the group in the list of fields
     * @param string $labelid language string id for the group label
     */
    private function add_exportfield_group($groupid, $labelid) {

        $mform = $this->_form;
        $fields = bookking_get_export_fields($this->bookking);
        $checkboxes = array();

        foreach ($fields as $field) {
            if ($field->get_group() == $groupid && $field->is_available($this->bookking)) {
                $inputid = 'field-'.$field->get_id();
                $label = $field->get_formlabel($this->bookking);
                $checkboxes[] = $mform->createElement('checkbox', $inputid, '', $label);
            }
        }
        $grouplabel = get_string($labelid, 'bookking');
        $mform->addGroup($checkboxes, 'fields-'.$groupid, $grouplabel, null, false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

}

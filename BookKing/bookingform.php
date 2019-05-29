<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Student-side form to book or edit an appointment in a selected slot
 */
class bookking_booking_form extends moodleform {

    protected $slot;
    protected $appointment = null;
    protected $uploadoptions;
    protected $existing;

    public function __construct(bookking_slot $slot, $action, $existing = false) {
        $this->slot = $slot;
        $this->existing = $existing;
        parent::__construct($action, null);
    }

    protected function definition() {

        global $CFG, $output;

        $mform = $this->_form;
        $bookking = $this->slot->get_bookking();

        $this->noteoptions = array('trusttext' => false, 'maxfiles' => 0, 'maxbytes' => 0,
                                   'context' => $bookking->get_context(),
                                   'collapsed' => true);

        $this->uploadoptions = array('subdirs' => 0,
                                     'maxbytes' => $bookking->uploadmaxsize,
                                     'maxfiles' => $bookking->uploadmaxfiles);

        // Text field for student-supplied data.
        if ($bookking->uses_studentnotes()) {

            $mform->addElement('editor', 'studentnote_editor', get_string('yourstudentnote', 'bookking'),
                                array('rows' => 3, 'columns' => 60), $this->noteoptions);
            $mform->setType('studentnote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
            if ($bookking->usestudentnotes == 2) {
                $mform->addRule('studentnote_editor', get_string('notesrequired', 'bookking'), 'required');
            }
        }

        // Student file upload.
        if ($bookking->uses_studentfiles()) {
            $mform->addElement('filemanager', 'studentfiles',
                    get_string('uploadstudentfiles', 'bookking'),
                    null, $this->uploadoptions );
            if ($bookking->requireupload) {
                $mform->addRule('studentfiles', get_string('uploadrequired', 'bookking'), 'required');
            }
        }

        // Captcha.
        if ($bookking->uses_bookingcaptcha() && !$this->existing) {
            $mform->addElement('recaptcha', 'bookingcaptcha', get_string('security_question', 'auth'), array('https' => true));
            $mform->addHelpButton('bookingcaptcha', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('bookingcaptcha');
        }

        $submitlabel = $this->existing ? null : get_string('confirmbooking', 'bookking');
        $this->add_action_buttons(true, $submitlabel);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!$this->existing && $this->slot->get_bookking()->uses_bookingcaptcha()) {
            $recaptcha = $this->_form->getElement('bookingcaptcha');
            if (!empty($this->_form->_submitValues['g-recaptcha-response'])) {
                $response = $this->_form->_submitValues['g-recaptcha-response'];
                if (true !== ($result = $recaptcha->verify($response))) {
                    $errors['bookingcaptcha'] = $result;
                }
            } else {
                $errors['bookingcaptcha'] = get_string('missingrecaptchachallengefield');
            }
        }

        return $errors;
    }

    public function prepare_booking_data(bookking_appointment $appointment) {
        $this->appointment = $appointment;

        $newdata = clone($appointment->get_data());
        $context = $appointment->get_bookking()->get_context();

        $newdata = file_prepare_standard_editor($newdata, 'studentnote', $this->noteoptions, $context);

        $draftitemid = file_get_submitted_draft_itemid('studentfiles');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_bookking', 'studentfiles', $appointment->id);
        $newdata->studentfiles = $draftitemid;

        return $newdata;
    }

    public function save_booking_data(stdClass $formdata, bookking_appointment $appointment) {
        $bookking = $appointment->get_bookking();
        if ($bookking->uses_studentnotes() && isset($formdata->studentnote_editor)) {
            $editor = $formdata->studentnote_editor;
            $appointment->studentnote = $editor['text'];
            $appointment->studentnoteformat = $editor['format'];
        }
        if ($bookking->uses_studentfiles()) {
            file_save_draft_area_files($formdata->studentfiles, $bookking->context->id,
                                       'mod_bookking', 'studentfiles', $appointment->id,
                                       $this->uploadoptions);
        }
        $appointment->save();
    }
}

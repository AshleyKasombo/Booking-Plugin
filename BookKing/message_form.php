<?php



defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

// Message form for invitations

class bookking_message_form extends moodleform {

    /**
     * @var bookking_instance bookking in whose context the messages are sent
     */
    protected $bookking;

    /**
     * Create a new messge form
     *
     * @param string $action
     * @param bookking_instance $bookking bookking in whose context the messages are sent
     * @param object $customdata
     */
    public function __construct($action, bookking_instance $bookking, $customdata=null) {
        $this->bookking = $bookking;
        parent::__construct($action, $customdata);
    }

    protected function definition() {

        $mform = $this->_form;

        // Select users to sent the message to.
        $checkboxes = array();
        $recipients = $this->_customdata['recipients'];
        foreach ($recipients as $recipient) {
            $inputid = 'recipient['.$recipient->id.']';
            $label = fullname($recipient);
            $checkboxes[] = $mform->createElement('checkbox', $inputid, '', $label);
            $mform->setDefault($inputid, true);
        }
        $mform->addGroup($checkboxes, 'recipients', get_string('recipients', 'bookking'), null, false);

        if (get_config('mod_bookking', 'showemailplain')) {
            $maillist = array();
            foreach ($recipients as $recipient) {
                $maillist[] = trim($recipient->email);
            }
            $maildisplay = html_writer::div(implode(', ', $maillist));
            $mform->addElement('html', $maildisplay);
        }

        $mform->addElement('selectyesno', 'copytomyself', get_string('copytomyself', 'bookking'));
        $mform->setDefault('copytomyself', true);

        $mform->addElement('text', 'subject', get_string('messagesubject', 'bookking'), array('size' => '60'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required');
        if (isset($this->_customdata['subject'])) {
            $mform->setDefault('subject', $this->_customdata['subject']);
        }

        $bodyedit = $mform->addElement('editor', 'body', get_string('messagebody', 'bookking'),
                                       array('rows' => 15, 'columns' => 60), array('collapsed' => true));
        $mform->setType('body', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        if (isset($this->_customdata['body'])) {
            $bodyedit->setValue(array('text' => $this->_customdata['body']));
        }

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('sendmessage', 'bookking'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

}

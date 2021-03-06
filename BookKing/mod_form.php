<?php

//Defines the bookking module settings form.



defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

// BookKing modedit form - overrides moodleform
 

class mod_bookking_mod_form extends moodleform_mod {

    protected $editoroptions;


    function definition() {

        global $CFG, $COURSE, $OUTPUT;
        $mform    =& $this->_form;

        // General introduction.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('introduction', 'bookking'));

        // BookKing options.
        $mform->addElement('header', 'optionhdr', get_string('options', 'bookking'));
        $mform->setExpanded('optionhdr');

        $mform->addElement('text', 'staffrolename', get_string('staffrolename', 'bookking'), array('size' => '48'));
        $mform->setType('staffrolename', PARAM_TEXT);
        $mform->addRule('staffrolename', get_string('error'), 'maxlength', 255);
        $mform->addHelpButton('staffrolename', 'staffrolename', 'bookking');

        $modegroup = array();
        $modegroup[] = $mform->createElement('static', 'modeintro', '', get_string('modeintro', 'bookking'));

        $maxbookoptions = array();
        $maxbookoptions['0'] = get_string('unlimited', 'bookking');
        for ($i = 1; $i <= 10; $i++) {
            $maxbookoptions[(string)$i] = $i;
        }
        $modegroup[] = $mform->createElement('select', 'maxbookings', '', $maxbookoptions);
        $mform->setDefault('maxbookings', 1);

        $modegroup[] = $mform->createElement('static', 'modeappointments', '', get_string('modeappointments', 'bookking'));

        $modeoptions['oneonly'] = get_string('modeoneonly', 'bookking');
        $modeoptions['onetime'] = get_string('modeoneatatime', 'bookking');
        $modegroup[] = $mform->createElement('select', 'bookkingmode', '', $modeoptions);
        $mform->setDefault('bookkingmode', 'oneonly');

        $mform->addGroup($modegroup, 'modegrp', get_string('mode', 'bookking'), ' ', false);
        $mform->addHelpButton('modegrp', 'appointmentmode', 'bookking');

        if (get_config('mod_bookking', 'groupscheduling')) {
            $selopt = array(
                            -1 => get_string('no'),
                             0 => get_string('yesallgroups', 'bookking')
                           );
            $groupings = groups_get_all_groupings($COURSE->id);
            foreach ($groupings as $grouping) {
                $selopt[$grouping->id] = get_string('yesingrouping', 'bookking', $grouping->name);
            }
            $mform->addElement('select', 'bookingrouping', get_string('groupbookings', 'bookking'), $selopt);
            $mform->addHelpButton('bookingrouping', 'groupbookings', 'bookking');
            $mform->setDefault('bookingrouping', '-1');
        }

        $mform->addElement('duration', 'guardtime', get_string('guardtime', 'bookking'), array('optional' => true));
        $mform->addHelpButton('guardtime', 'guardtime', 'bookking');

        $mform->addElement('text', 'defaultslotduration', get_string('defaultslotduration', 'bookking'), array('size' => '2'));
        $mform->setType('defaultslotduration', PARAM_INT);
        $mform->addHelpButton('defaultslotduration', 'defaultslotduration', 'bookking');
        $mform->setDefault('defaultslotduration', 15);

        $mform->addElement('selectyesno', 'allownotifications', get_string('notifications', 'bookking'));
        $mform->addHelpButton('allownotifications', 'notifications', 'bookking');

        $noteoptions['0'] = get_string('usenotesnone', 'bookking');
        $noteoptions['1'] = get_string('usenotesstudent', 'bookking');
        $noteoptions['2'] = get_string('usenotesteacher', 'bookking');
        $noteoptions['3'] = get_string('usenotesboth', 'bookking');
        $mform->addElement('select', 'usenotes', get_string('usenotes', 'bookking'), $noteoptions);
        $mform->setDefault('usenotes', '1');

        // Grade settings.

/*        $this->standard_grading_coursemodule_elements();

        $mform->setDefault('grade', 0);

        $gradingstrategy[BOOKKING_MEAN_GRADE] = get_string('meangrade', 'bookking');
        $gradingstrategy[BOOKKING_MAX_GRADE] = get_string('maxgrade', 'bookking');
        $mform->addElement('select', 'gradingstrategy', get_string('gradingstrategy', 'bookking'), $gradingstrategy);
        $mform->addHelpButton('gradingstrategy', 'gradingstrategy', 'bookking');
        $mform->disabledIf('gradingstrategy', 'grade[modgrade_type]', 'eq', 'none');
*/

        // Booking form and student-supplied data.
        $mform->addElement('header', 'bookinghdr', get_string('bookingformoptions', 'bookking'));

        $mform->addElement('selectyesno', 'usebookingform', get_string('usebookingform', 'bookking'));
        $mform->addHelpButton('usebookingform', 'usebookingform', 'bookking');

        $this->editoroptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                     'context' => $this->context, 'collapsed' => true);
        $mform->addElement('editor', 'bookinginstructions_editor', get_string('bookinginstructions', 'bookking'),
                array('rows' => 3, 'columns' => 60), $this->editoroptions);
        $mform->setType('bookinginstructions', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        $mform->disabledIf('bookinginstructions_editor', 'usebookingform', 'eq', '0');
        $mform->addHelpButton('bookinginstructions_editor', 'bookinginstructions', 'bookking');

        $studentnoteoptions['0'] = get_string('no');
        $studentnoteoptions['1'] = get_string('yesoptional', 'bookking');
        $studentnoteoptions['2'] = get_string('yesrequired', 'bookking');
        $mform->addElement('select', 'usestudentnotes', get_string('usestudentnotes', 'bookking'), $studentnoteoptions);
        $mform->setDefault('usestudentnotes', '0');
        $mform->disabledIf('usestudentnotes', 'usebookingform', 'eq', '0');
        $mform->addHelpButton('usestudentnotes', 'usestudentnotes', 'bookking');

        $uploadgroup = array();

/*        $filechoices = array();
        for ($i = 0; $i <= get_config('mod_bookking', 'uploadmaxfiles'); $i++) {
            $filechoices[$i] = $i;
        }
        $uploadgroup[] = $mform->createElement('select', 'uploadmaxfiles', get_string('uploadmaxfiles', 'bookking'), $filechoices);
        $mform->setDefault('uploadmaxfiles', 0);
        $mform->disabledIf('uploadmaxfiles', 'usebookingform', 'eq', '0');
        $uploadgroup[] = $mform->createElement('advcheckbox', 'requireupload', '', get_string('requireupload', 'bookking'));
        $mform->disabledIf('requireupload', 'usebookingform', 'eq', '0');

        $mform->addGroup($uploadgroup, 'uploadgrp', get_string('uploadmaxfiles', 'bookking'), ' ', false);
        $mform->addHelpButton('uploadgrp', 'uploadmaxfiles', 'bookking');

        $sizechoices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes, 0);
        $mform->addElement('select', 'uploadmaxsize', get_string('uploadmaxsize', 'bookking'), $sizechoices);
        $mform->setDefault('assignsubmission_file_maxsizebytes', $COURSE->maxbytes);
        $mform->disabledIf('uploadmaxsize', 'usebookingform', 'eq', '0');
        $mform->disabledIf('uploadmaxsize', 'uploadmaxfiles', 'eq', '0');
        $mform->addHelpButton('uploadmaxsize', 'uploadmaxsize', 'bookking');

        if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey)) {
            $mform->addElement('selectyesno', 'usecaptcha', get_string('usecaptcha', 'bookking'), $studentnoteoptions);
            $mform->setDefault('usecaptcha', '0');
            $mform->disabledIf('usecaptcha', 'usebookingform', 'eq', '0');
            $mform->addHelpButton('usecaptcha', 'usecaptcha', 'bookking');
        }

*/




        // Common module settings.
        $this->standard_coursemodule_elements();
        $mform->setDefault('groupmode', NOGROUPS);

        $this->add_action_buttons();
    }

    function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        if ($this->current->instance) {
            $newvalues = file_prepare_standard_editor((object)$defaultvalues, 'bookinginstructions',
                             $this->editoroptions, $this->context,
                            'mod_bookking', 'bookinginstructions', 0);
            $defaultvalues['bookinginstructions_editor'] = $newvalues->bookinginstructions_editor;
        }
        if (array_key_exists('scale', $defaultvalues)) {
            $dgrade = $defaultvalues['scale'];
            $defaultvalues['grade'] = $dgrade;
            $type = 'none';
            if ($dgrade > 0) {
                $type = 'point';
            } else if ($dgrade < 0) {
                $type = 'scale';
            }
            $defaultvalues['grade[modgrade_type]'] = $type;
        }
    }

    public function save_mod_data(stdClass $data, context_module $context) {
        global $DB;

        $editor = $data->bookinginstructions_editor;
        if ($editor) {
            $data->bookinginstructions = file_save_draft_area_files($editor['itemid'], $context->id,
                                            'mod_bookking', 'bookinginstructions', 0,
                                            $this->editoroptions, $editor['text']);
            $data->bookinginstructionsformat = $editor['format'];
            $DB->update_record('bookking', $data);
        }
    }



}

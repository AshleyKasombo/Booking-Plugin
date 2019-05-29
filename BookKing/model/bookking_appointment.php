<?php

/**
 * A class for representing a bookking appointment.
 *
 */


defined('MOODLE_INTERNAL') || die();

require_once('modellib.php');


/**
 * A class for representing a bookking appointment.
 *
 */
class bookking_appointment extends mvc_child_record_model {


    protected function get_table() {
        return 'bookking_appointment';
    }

    public function __construct(bookking_slot $slot) {
        parent::__construct();
        $this->data = new stdClass();
        $this->set_parent($slot);
        $this->data->slotid = $slot->get_id();
        $this->data->attended = 0;
        $this->data->appointmentnoteformat = FORMAT_HTML;
        $this->data->teachernoteformat = FORMAT_HTML;
    }

    public function save() {
        $this->data->slotid = $this->get_parent()->get_id();
        parent::save();
        $scheddata = $this->get_bookking()->get_data();
        bookking_update_grades($scheddata, $this->studentid);
    }

    public function delete() {
        $studid = $this->studentid;
        parent::delete();

        $scheddata = $this->get_bookking()->get_data();
        bookking_update_grades($scheddata, $studid);

        $fs = get_file_storage();
        $cid = $this->get_bookking()->get_context()->id;
        $fs->delete_area_files($cid, 'mod_bookking', 'appointmentnote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_bookking', 'teachernote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_bookking', 'studentnote', $this->get_id());

    }

    /**
     * Retrieve the slot associated with this appointment
     *
     * @return bookking_slot;
     */
    public function get_slot() {
        return $this->get_parent();
    }

    /**
     * Retrieve the bookking associated with this appointment
     *
     * @return bookking_instance
     */
    public function get_bookking() {
        return $this->get_parent()->get_parent();
    }

    /**
     * Return the student object.
     * May be null if no student is assigned to this appointment (this _should_ never happen).
     */
    public function get_student() {
        global $DB;
        if ($this->data->studentid) {
            return $DB->get_record('user', array('id' => $this->data->studentid), '*', MUST_EXIST);
        } else {
            return null;
        }
    }

    /**
     * Has this student attended?
     */
    public function is_attended() {
        return (boolean) $this->data->attended;
    }

    /**
     * Are there any student notes associated with this appointment?
     * @return boolean
     */
    public function has_studentnotes() {
        return $this->get_bookking()->uses_studentnotes() &&
                strlen(trim(strip_tags($this->studentnote))) > 0;
    }

    /**
     * How many files has the student uploaded for this appointment?
     *
     * @return int
     */
    public function count_studentfiles() {
        if (!$this->get_bookking()->uses_studentnotes()) {
            return 0;
        }
        $ctx = $this->get_bookking()->context->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($ctx, 'mod_bookking', 'studentfiles', $this->id, "filename", false);
        return count($files);
    }

}

/**
 * A factory class for bookking appointments.
 *
 */
class bookking_appointment_factory extends mvc_child_model_factory {
    public function create_child(mvc_record_model $parent) {
        return new bookking_appointment($parent);
    }
}

<?php

/**
 * Base class for appointment-based events.
 */

namespace mod_bookking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_bookking abstract base event class for appointment-based events.
 *
 */
abstract class appointment_base extends \core\event\base {


    /**
     * @var \bookking_appointment the appointment associated with this event
     */
    protected $appointment;

    /**
     * Return the base data fields for an appointment
     *
     * @param \bookking_appointment $appointment the appointment in question
     * @return array
     */
    protected static function base_data(\bookking_appointment $appointment) {
        return array(
            'context' => $appointment->get_parent()->get_context(),
            'objectid' => $appointment->id
        );
    }

    /**
     * Set data of the event from an appointment record.
     *
     * @param \bookking_appointment $appointment
     */
    protected function set_appointment(\bookking_appointment $appointment) {
        $this->add_record_snapshot('bookking_appointment', $appointment->data);
        $this->add_record_snapshot('bookking_slots', $appointment->get_parent()->data);
        $this->add_record_snapshot('bookking', $appointment->get_parent()->get_parent()->data);
        $this->appointment = $appointment;
        $this->data['objecttable'] = 'bookking_appointments';
    }

    /**
     * Get appointment object.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \bookking_appointment
     */
    public function get_appointment() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_appointment() is intended for event observers only');
        }
        return $this->appointment;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/bookking/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}

<?php

/**
 * Defines a base class for slot-based events.
 *
 */

namespace mod_bookking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_bookking abstract base event class for slot-based events.
 */
abstract class slot_base extends \core\event\base {

    /**
     * @var \bookking_slot the slot associated with this event
     */
    protected $slot;

    /**
     * Return the base data fields for a slot
     *
     * @param \bookking_slot $slot the slot in question
     * @return array
     */
    protected static function base_data(\bookking_slot $slot) {
        return array(
            'context' => $slot->get_bookking()->get_context(),
            'objectid' => $slot->id,
            'relateduserid' => $slot->teacherid
        );
    }

    /**
     * Set the slot associated with this event
     *
     * @param \bookking_slot $slot
     */
    protected function set_slot(\bookking_slot $slot) {
        $this->add_record_snapshot('bookking_slots', $slot->data);
        $this->add_record_snapshot('bookking', $slot->get_bookking()->data);
        $this->slot = $slot;
        $this->data['objecttable'] = 'bookking_slots';
    }

    /**
     * Get slot object.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \bookking_slot
     */
    public function get_slot() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_slot() is intended for event observers only');
        }
        return $this->slot;
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

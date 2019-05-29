<?php

/**
 * Defines the mod_bookking slot added event.
 *
 */

namespace mod_bookking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_bookking slot added event.
 *
 * Indicates that a teacher has added a slot.
 *
 */
class slot_added extends slot_base {

    /**
     * Create this event on a given slot.
     *
     * @param \bookking_slot $slot
     * @return \core\event\base
     */
    public static function create_from_slot(\bookking_slot $slot) {
        $event = self::create(self::base_data($slot));
        $event->set_slot($slot);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_slotadded', 'bookking');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' created the slot with id  '{$this->objectid}'"
                ." in the bookking with course module id '$this->contextinstanceid'.";
    }
}

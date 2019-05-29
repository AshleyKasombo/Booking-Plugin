<?php

/**
 * Defines the mod_bookking booking form removed event.
 *
 */

namespace mod_bookking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_bookking booking form removed event.
 *
 * Indicates that a student has removed their booking from a slot.
 *
 */
class booking_removed extends slot_base {

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
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_bookingremoved', 'bookking');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has removed their booking from the slot with id  '{$this->objectid}'"
                ." in the bookking with course module id '$this->contextinstanceid'.";
    }
}

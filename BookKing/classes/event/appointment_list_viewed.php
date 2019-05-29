<?php

/**
 * Defines the mod_bookking appointment list viewed event.
 */

namespace mod_bookking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_bookking appointment list viewed event.
 *
 * Indicates that a teacher has viewed the list of appointments and slots.
 *
 */
 class appointment_list_viewed extends bookking_base {

    /**
     * Create this event on a given bookking.
     *
     * @param \bookking_instance $bookking
     * @return \core\event\base
     */
    public static function create_from_bookking(\bookking_instance $bookking) {
        $event = self::create(self::base_data($bookking));
        $event->set_bookking($bookking);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_appointmentlistviewed', 'bookking');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has viewed the list of appointments in the bookking with course module id '$this->contextinstanceid'.";
    }
}

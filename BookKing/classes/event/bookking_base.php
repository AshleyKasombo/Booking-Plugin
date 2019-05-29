<?php

/**
 * Defines a base class for bookking events.
 *
 */

namespace mod_bookking\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_bookking abstract base event class.
 *
 */
abstract class bookking_base extends \core\event\base {

    /**
     * @var \bookking_instance the bookking associated with this event
     */
    protected $bookking;

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;

    /**
     * Retrieve base data for this event from a bookking.
     *
     * @param \bookking_instance $bookking
     * @return array
     */
    protected static function base_data(\bookking_instance $bookking) {
        return array(
            'context' => $bookking->get_context(),
            'objectid' => $bookking->id
        );
    }

    /**
     * Set the bookking associated with this event.
     *
     * @param \bookking_instance $bookking
     */
    protected function set_bookking(\bookking_instance $bookking) {
        $this->add_record_snapshot('bookking', $bookking->data);
        $this->bookking = $bookking;
        $this->data['objecttable'] = 'bookking';
    }

    /**
     * Get bookking instance.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \bookking_instance
     */
    public function get_bookking() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_bookking() is intended for event observers only');
        }
        if (!isset($this->bookking)) {
            debugging('bookking property should be initialised in each event', DEBUG_DEVELOPER);
            global $CFG;
            require_once($CFG->dirroot . '/mod/bookking/locallib.php');
            $this->bookking = \bookking_instance::load_by_coursemodule_id($this->contextinstanceid);
        }
        return $this->bookking;
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
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'bookking';
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

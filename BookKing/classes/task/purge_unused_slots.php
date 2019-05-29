<?php

/**
 * Scheduled background task for sending automated appointment reminders
 *
 */

namespace mod_bookking\task;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../model/bookking_instance.php');

/**
 * Scheduled background task for sending automated appointment reminders
 *
 */
 class purge_unused_slots extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('purgeunusedslots', 'mod_bookking');
    }

    public function execute() {
        \bookking_instance::free_late_unused_slots();
    }
}

<?php

/**
 * Scheduled background tasks in the bookking module
 *
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
            array(
                'classname' => 'mod_bookking\task\send_reminders',
                'minute' => 'R',
                'hour' => '*',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*'
            ),
            array(
                'classname' => 'mod_bookking\task\purge_unused_slots',
                'minute' => '*/5',
                'hour' => '*',
                'day' => '*',
                'dayofweek' => '*',
                'month' => '*'
            )
);

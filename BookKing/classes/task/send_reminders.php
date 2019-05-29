<?php

/**
 * Scheduled background task for sending automated appointment reminders
 *
 */

namespace mod_bookking\task;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../model/bookking_instance.php');
require_once(dirname(__FILE__).'/../../model/bookking_slot.php');
require_once(dirname(__FILE__).'/../../model/bookking_appointment.php');
require_once(dirname(__FILE__).'/../../mailtemplatelib.php');

/**
 * Scheduled background task for sending automated appointment reminders
 *
 */
 class send_reminders extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('sendreminders', 'mod_bookking');
    }

    public function execute() {

        global $DB;

        $date = make_timestamp(date('Y'), date('m'), date('d'), date('H'), date('i'));

        // Find relevant slots in all bookkings.
        $select = 'emaildate > 0 AND emaildate <= ? AND starttime > ?';
        $slots = $DB->get_records_select('bookking_slots', $select, array($date, $date), 'starttime');

        foreach ($slots as $slot) {
            // Get teacher record.
            $teacher = $DB->get_record('user', array('id' => $slot->teacherid));

            // Get bookking, slot and course.
            $bookking = \bookking_instance::load_by_id($slot->bookkingid);
            $slotm = $bookking->get_slot($slot->id);
            $course = $bookking->get_courserec();

            // Mark as sent. (Do this first for safe fallback in case of an exception.)
            $slot->emaildate = -1;
            $DB->update_record('bookking_slots', $slot);

            // Send reminder to all students in the slot.
            foreach ($slotm->get_appointments() as $appointment) {
                $student = $DB->get_record('user', array('id' => $appointment->studentid));
                cron_setup_user($student, $course);
                \bookking_messenger::send_slot_notification($slotm,
                        'reminder', 'reminder', $teacher, $student, $teacher, $student, $course);
            }
        }
        cron_setup_user();
    }

}

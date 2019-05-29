<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for mod_bookking.
 */

namespace mod_bookking\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\content_writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the bookking activity module.
 *
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider {


    private static $renderer;

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'bookking_slots',
            [
                'teacherid' => 'privacy:metadata:bookking_slots:teacherid',
                'starttime' => 'privacy:metadata:bookking_slots:starttime',
                'duration'  => 'privacy:metadata:bookking_slots:duration',
                'appointmentlocation' => 'privacy:metadata:bookking_slots:appointmentlocation',
                'notes' => 'privacy:metadata:bookking_slots:notes',
                'notesformat' => 'privacy:metadata:bookking_slots:notesformat',
                'exclusivity' => 'privacy:metadata:bookking_slots:exclusivity'
                 // The fields "timemodified", "emaildate" and "hideuntil" do not contain personal data.
            ],
            'privacy:metadata:bookking_slots'
        );
        $collection->add_database_table(
            'bookking_appointment',
            [
                'studentid' => 'privacy:metadata:bookking_appointment:studentid',
                'attended' => 'privacy:metadata:bookking_appointment:attended',
                'grade' => 'privacy:metadata:bookking_appointment:grade',
                'appointmentnote' => 'privacy:metadata:bookking_appointment:appointmentnote',
                'appointmentnoteformat' => 'privacy:metadata:bookking_appointment:appointmentnoteformat',
                'teachernote' => 'privacy:metadata:bookking_appointment:teachernote',
                'teachernoteformat' => 'privacy:metadata:bookking_appointment:teachernoteformat',
                'studentnote' => 'privacy:metadata:bookking_appointment:studentnote',
                'studentnoteformat' => 'privacy:metadata:bookking_appointment:studentnoteformat'
                // The fields "timecreated" and "timemodifed" are technical only, they do not contain personal data.
            ],
            'privacy:metadata:bookking_appointment'
        );

        // Subsystems used.
        $collection->link_subsystem('core_files', 'privacy:metadata:filepurpose');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Fetch all bookking records for teachers.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {bookking} s ON s.id = cm.instance
            INNER JOIN {bookking_slots} t ON t.bookkingid = s.id
                 WHERE t.teacherid = :userid";

        $params = [
            'modname'       => 'bookking',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        // Fetch all bookking records for students.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {bookking} s ON s.id = cm.instance
            INNER JOIN {bookking_slots} t ON t.bookkingid = s.id
            INNER JOIN {bookking_appointment} a ON a.slotid = t.id
                 WHERE a.studentid = :userid";

        $params = [
                'modname'       => 'bookking',
                'contextlevel'  => CONTEXT_MODULE,
                'userid'        => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        // Fetch teachers.
        $sql = "SELECT t.teacherid
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {bookking} s ON s.id = cm.instance
            INNER JOIN {bookking_slots} t ON t.bookkingid = s.id
                 WHERE cm.id = :cmid";

        $params = [
                'modname'       => 'bookking',
                'cmid'          => $context->instanceid
        ];

        $userlist->add_from_sql('teacherid', $sql, $params);

        // Fetch students.
        $sql = "SELECT a.studentid
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {bookking} s ON s.id = cm.instance
            INNER JOIN {bookking_slots} t ON t.bookkingid = s.id
            INNER JOIN {bookking_appointment} a ON a.slotid = t.id
                 WHERE cm.id = :cmid";

        $params = [
                'modname'       => 'bookking',
                'cmid'          => $context->instanceid
        ];

        $userlist->add_from_sql('studentid', $sql, $params);

        return $userlist;
    }

    /**
     * Load a bookking instance from a context.
     *
     * Will return null if the context was not found.
     *
     * @param \context $context the context of the bookking.
     * @return \bookking_instance bookking object, or null if not found.
     */
    private static function load_bookking_for_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return null;
        }

        $sql = "SELECT s.id as bookkingid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {bookking} s ON s.id = cm.instance
                WHERE cm.id = :cmid";
        $params = ['cmid' => $context->instanceid, 'modname' => 'bookking'];
        $rec = $DB->get_record_sql($sql, $params);
        if ($rec) {
            return \bookking_instance::load_by_id($rec->bookkingid);
        } else {
            return null;
        }

    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }

        self::$renderer = new \mod_bookking_renderer();

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT cm.id AS cmid, s.name AS bookkingname, s.id as bookkingid, cm.course AS courseid,
                t.id as slotid, t.teacherid, t.starttime, t.duration,
                t.appointmentlocation, t.notes, t.notesformat, t.exclusivity,
                a.id as appointmentid,
                a.studentid, a.attended, a.grade,
                a.appointmentnote, a.appointmentnoteformat,
                a.teachernote, a.teachernoteformat,
                a.studentnote, a.studentnoteformat
                FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {bookking} s ON s.id = cm.instance
                JOIN {bookking_slots} t ON t.bookkingid = s.id
                JOIN {bookking_appointment} a ON a.slotid = t.id
                WHERE ctx.id {$contextsql} AND ctx.contextlevel = :contextlevel
                AND t.teacherid = :userid1 OR a.studentid = :userid2
                ORDER BY cm.id, t.id, a.id";
        $rs = $DB->get_recordset_sql($sql, $contextparams + ['contextlevel' => CONTEXT_MODULE,
                'modname' => 'bookking', 'userid1' => $user->id, 'userid2' => $user->id]);

        $context = null;
        $lastrow = null;
        $bookking = null;
        foreach ($rs as $row) {
            if (!$context || $context->instanceid != $row->cmid) {
                // This row belongs to the different bookking than the previous row.
                // Export the data for the previous module.
                self::export_bookking($context, $user);
                // Start new bookking module.
                $context = \context_module::instance($row->cmid);
                $bookking = \bookking_instance::load_by_id($row->bookkingid);
            }

            if (!$lastrow || $row->slotid != $lastrow->slotid) {
                // Export previous slot record.
                self::export_slot($context, $user, $row);
            }
            self::export_appointment($context, $bookking, $user, $row);
            $lastrow = $row;
        }
        $rs->close();
        self::export_slot($context, $user, $lastrow);
        self::export_bookking($context, $user);
    }

    private static function format_note($notetext, $noteformat, $filearea, $id,
            \context $context, content_writer $wrc, $exportarea) {
        $message = $notetext;
        if ($filearea) {
            $message = $wrc->rewrite_pluginfile_urls($exportarea, 'mod_bookking', $filearea, $id, $notetext);
        }
        $opts = (object) [
                'para'    => false,
                'context' => $context
        ];
        $message = format_text($message, $noteformat, $opts);
        return $message;
    }

    /**
     * Export one slot in a bookking (one record in {bookking_slots} table)
     *
     * @param \context $context
     * @param \stdClass $user
     * @param \stdClass $record
     */
    protected static function export_slot($context, $user, $record) {
        if (!$record) {
            return;
        }
        $slotarea = ['slot '.$record->slotid];
        $wrc = writer::with_context($context);

        $data = [
            'teacherid' => transform::user($record->teacherid),
            'starttime' => transform::datetime($record->starttime),
            'duration'  => $record->duration,
            'appointmentlocation' => format_string($record->appointmentlocation),
            'notes' => self::format_note($record->notes, $record->notesformat,
                                         'slotnote', $record->slotid, $context, $wrc, $slotarea),
            'exclusivity' => $record->exclusivity,
        ];

        // Data about the slot.
        $wrc->export_data($slotarea, (object)$data);
        $wrc->export_area_files($slotarea, 'mod_bookking', 'slotnote', $record->slotid);
    }

    /**
     * Export one appointment in a bookking (one record in {bookking_appointment} table)
     *
     * @param \context $context
     * @param \bookking_instance $bookking
     * @param \stdClass $user
     * @param \stdClass $record
     */
    protected static function export_appointment($context, $bookking, $user, $record) {
        if (!$record) {
            return;
        }
        $wrc = writer::with_context($context);
        $apparea = ['slot '.$record->slotid, 'appointment '.$record->appointmentid];

        $revealteachernote = ($user->id == $record->teacherid) ||
                             get_config('mod_bookking', 'revealteachernotes');

        $data = [
                'studentid' => transform::user($record->studentid),
                'attended' => transform::yesno($record->attended),
                'grade' => self::$renderer->format_grade($bookking, $record->grade),
                'appointmentnote' => self::format_note($record->appointmentnote, $record->appointmentnoteformat,
                                         'appointmentnote', $record->appointmentid, $context, $wrc, $apparea),
                'studentnote' => self::format_note($record->studentnote, $record->studentnoteformat,
                                     '', 0, $context, $wrc, $apparea),
        ];
        if ($revealteachernote) {
            $data['teachernote'] = self::format_note($record->teachernote, $record->teachernoteformat,
                                       'teachernote', $record->appointmentid, $context, $wrc, $apparea);
        }

        // Data about the appointment.

        $wrc->export_data($apparea, (object)$data);

        $wrc->export_area_files($apparea, 'mod_bookking', 'appointmentnote', $record->appointmentid);
        if ($revealteachernote) {
            $wrc->export_area_files($apparea, 'mod_bookking', 'teachernote', $record->appointmentid);
        }
        $wrc->export_area_files($apparea, 'mod_bookking', 'studentfiles', $record->appointmentid);
    }

    /**
     * Export basic info about a bookking activity module
     *
     * @param \context $context
     * @param \stdClass $user
     */
    protected static function export_bookking($context, $user) {
        if (!$context) {
            return;
        }
        $contextdata = helper::get_context_data($context, $user);
        helper::export_context_files($context, $user);
        writer::with_context($context)->export_data([], $contextdata);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * This will delete both slots and appointments for all users.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($bookking = self::load_bookking_for_context($context)) {
            $bookking->delete_all_slots();
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * This will delete only appointments where the specified user is a student.
     * No data will be deleted if the user is (only) a teacher for the relevant slot/appointment,
     * since deleting it may lose data for other users (namely, the students).
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {

            if ($bookking = self::load_bookking_for_context($context)) {
                $apps = $bookking->get_appointments_for_student($user->id);
                foreach ($apps as $app) {
                    $app->delete();
                }
            }
        }
    }

    /**
     * Delete all user data for the specified users (plural), in the specified context.
     *
     * This will delete only appointments where the specified user is a student.
     * No data will be deleted if the user is (only) a teacher for the relevant slot/appointment,
     * since deleting it may lose data for other users (namely, the students).
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        $context = $userlist->get_context();
        $users = $userlist->get_userids();

        if ($bookking = self::load_bookking_for_context($context)) {
            foreach ($users as $userid) {
                $apps = $bookking->get_appointments_for_student($userid);
                foreach ($apps as $app) {
                    $app->delete();
                }
            }
        }
    }

}

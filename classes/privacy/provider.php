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

namespace block_course_recommender\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for block_course_recommender.
 *
 * The block only stores personal data when the admin enables the "Remember selected
 * interests" setting (interest_store::is_enabled()). The stored data - a user's selected
 * interest tags - lives entirely in that user's own context (CONTEXT_USER), never shared
 * with other users, so no core_userlist_provider is needed.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'course_recommender_interest',
            [
                'userid' => 'privacy:metadata:course_recommender_interest:userid',
                'tagid' => 'privacy:metadata:course_recommender_interest:tagid',
                'timecreated' => 'privacy:metadata:course_recommender_interest:timecreated',
            ],
            'privacy:metadata:course_recommender_interest'
        );

        $collection->add_external_location_link(
            'embedding_service',
            [
                'interests' => 'privacy:metadata:embedding_service:interests',
                'completedcourses' => 'privacy:metadata:embedding_service:completedcourses',
                'competencies' => 'privacy:metadata:embedding_service:competencies',
            ],
            'privacy:metadata:embedding_service'
        );

        $collection->add_subsystem_link('core_ai', [], 'privacy:metadata:core_ai');

        return $collection;
    }

    /**
     * Get the list of contexts that contain personal data for the given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        if ($DB->record_exists('course_recommender_interest', ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }

        return $contextlist;
    }

    /**
     * Export personal data for the approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_USER || (int) $context->instanceid !== $userid) {
                continue;
            }

            $sql = "SELECT t.rawname, i.timecreated
                      FROM {course_recommender_interest} i
                      JOIN {tag} t ON t.id = i.tagid
                     WHERE i.userid = :userid";
            $records = $DB->get_records_sql($sql, ['userid' => $userid]);

            $data = array_map(static function ($record) {
                return (object) [
                    'interest' => $record->rawname,
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                ];
            }, array_values($records));

            if (!empty($data)) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'block_course_recommender')],
                    (object) ['interests' => $data]
                );
            }
        }
    }

    /**
     * Delete all personal data for all users in the given context.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $DB->delete_records('course_recommender_interest', ['userid' => $context->instanceid]);
    }

    /**
     * Delete personal data for the user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_USER || (int) $context->instanceid !== $userid) {
                continue;
            }
            $DB->delete_records('course_recommender_interest', ['userid' => $userid]);
        }
    }
}

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

namespace block_course_recommender;

/**
 * Persists a user's selected interest tags between sessions.
 *
 * Feature is opt-in via the 'persistinterests' admin setting; callers must check
 * {@see self::is_enabled()} before reading/writing so the feature can be fully disabled
 * without leaving stale reads in place.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class interest_store {
    /** @var string DB table name (without prefix). */
    const TABLE = 'course_recommender_interest';

    /**
     * Whether the admin has enabled persisting interest selections.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return !empty(get_config('block_course_recommender', 'persistinterests'));
    }

    /**
     * Replace a user's stored interests with the given set of tag ids.
     *
     * @param int $userid
     * @param array $tagids
     * @return void
     */
    public static function save(int $userid, array $tagids): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['userid' => $userid]);

        $now = time();
        $records = [];
        foreach (array_unique($tagids) as $tagid) {
            $records[] = (object) [
                'userid' => $userid,
                'tagid' => (int) $tagid,
                'timecreated' => $now,
            ];
        }
        if (!empty($records)) {
            $DB->insert_records(self::TABLE, $records);
        }
    }

    /**
     * Remove all stored interests for a user.
     *
     * @param int $userid
     * @return void
     */
    public static function delete_for_user(int $userid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Get a user's stored interest tags as raw tag names.
     *
     * @param int $userid
     * @return array List of tag rawnames.
     */
    public static function get_tagnames(int $userid): array {
        global $DB;

        $sql = "SELECT t.id, t.rawname
                  FROM {" . self::TABLE . "} i
                  JOIN {tag} t ON t.id = i.tagid
                 WHERE i.userid = :userid";
        $records = $DB->get_records_sql($sql, ['userid' => $userid]);

        return array_map(static function ($record) {
            return $record->rawname;
        }, array_values($records));
    }

    /**
     * Whether a user has any stored interests.
     *
     * @param int $userid
     * @return bool
     */
    public static function has_data(int $userid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE, ['userid' => $userid]);
    }
}

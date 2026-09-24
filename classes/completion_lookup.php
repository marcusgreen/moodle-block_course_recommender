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
 * Looks up a user's completed courses for personalisation.
 *
 * Feature is opt-in via the 'completionfilter' admin setting; callers must check
 * {@see self::is_enabled()} before reading, consistent with {@see interest_store::is_enabled()}.
 * This is always a live read of core completion data - nothing is persisted by this plugin.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_lookup {
    /**
     * Whether the admin has enabled completion-based personalisation.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        return !empty(get_config('block_course_recommender', 'completionfilter'));
    }

    /**
     * Get the ids of courses the given user has fully completed.
     *
     * Only considers courses with completion tracking enabled - a course never has a
     * {course_completions} row for anyone if enablecompletion = 0.
     *
     * @param int $userid
     * @return array Course ids.
     */
    public static function get_completed_course_ids(int $userid): array {
        global $DB;

        $sql = "SELECT cc.course
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course AND c.enablecompletion = 1
                 WHERE cc.userid = :userid
                   AND cc.timecompleted > 0";
        return array_values($DB->get_fieldset_sql($sql, ['userid' => $userid]));
    }

    /**
     * Build embeddable text (title + tags + summary) for a user's most recently completed
     * courses, for use as a semantic signal in {@see reranker::rerank()}.
     *
     * Capped to $limit courses (most recent first) to bound embedding API calls - a student
     * with years of completions doesn't need all of them to nudge ranking.
     *
     * @param int $userid
     * @param int $limit
     * @return string[] One text blob per completed course.
     */
    public static function get_completed_course_texts(int $userid, int $limit = 10): array {
        global $DB;

        $sql = "SELECT cc.course, c.fullname, c.summary, c.summaryformat
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course AND c.enablecompletion = 1
                 WHERE cc.userid = :userid
                   AND cc.timecompleted > 0
              ORDER BY cc.timecompleted DESC";
        $records = $DB->get_records_sql($sql, ['userid' => $userid], 0, $limit);
        if (empty($records)) {
            return [];
        }

        $courseids = array_keys($records);
        $tagsbycourse = \core_tag_tag::get_items_tags('core', 'course', $courseids);

        $texts = [];
        foreach ($records as $courseid => $course) {
            $tagnames = [];
            if (!empty($tagsbycourse[$courseid])) {
                foreach ($tagsbycourse[$courseid] as $tag) {
                    $tagnames[] = $tag->rawname;
                }
            }
            $summary = '';
            if (!empty($course->summary)) {
                $summary = trim(strip_tags(format_text($course->summary, $course->summaryformat, [
                    'noclean' => false,
                    'overflowdiv' => false,
                    'para' => false,
                ])));
            }
            $text = trim(format_string($course->fullname) . ' ' . implode(' ', $tagnames) . ' ' . $summary);
            if ($text !== '') {
                $texts[] = $text;
            }
        }
        return $texts;
    }
}

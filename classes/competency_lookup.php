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
 * Looks up a user's competencies for personalisation.
 *
 * Feature is opt-in via the 'competencyfilter' admin setting; callers must check
 * {@see self::is_enabled()} before reading, consistent with {@see interest_store::is_enabled()}
 * and {@see completion_lookup::is_enabled()}.
 * This is always a live read of core_competency data - nothing is persisted by this plugin.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_lookup {
    /**
     * Whether the admin has enabled competency-based personalisation.
     *
     * @return bool
     */
    public static function is_enabled(): bool {
        if (empty(get_config('block_course_recommender', 'competencyfilter'))) {
            return false;
        }
        return \core_component::get_component_directory('core_competency') !== null;
    }

    /**
     * Get the ids of competencies the given user holds, across all of their learning plans.
     *
     * Only considers competencies marked complete/proficient in a plan - a competency merely
     * added to a plan but not yet achieved isn't a signal of existing knowledge.
     *
     * @param int $userid
     * @return array Competency ids.
     */
    public static function get_competency_ids(int $userid): array {
        if (!class_exists('\core_competency\api')) {
            return [];
        }

        $ids = [];
        foreach (\core_competency\api::list_user_plans($userid) as $plan) {
            foreach (\core_competency\api::list_plan_competencies($plan) as $plancompetency) {
                $usercompetency = $plancompetency->get_user_competency();
                if ($usercompetency && $usercompetency->get('proficiency')) {
                    $ids[] = $plancompetency->get_competency()->get('id');
                }
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Build embeddable text (name + description) for a user's proficient competencies,
     * for use as a semantic signal in {@see reranker::rerank()}.
     *
     * Capped to $limit competencies to bound embedding API calls.
     *
     * @param int $userid
     * @param int $limit
     * @return string[] One text blob per competency.
     */
    public static function get_competency_texts(int $userid, int $limit = 10): array {
        if (!class_exists('\core_competency\api')) {
            return [];
        }

        $texts = [];
        foreach (\core_competency\api::list_user_plans($userid) as $plan) {
            foreach (\core_competency\api::list_plan_competencies($plan) as $plancompetency) {
                if (count($texts) >= $limit) {
                    break 2;
                }
                $usercompetency = $plancompetency->get_user_competency();
                if (!$usercompetency || !$usercompetency->get('proficiency')) {
                    continue;
                }
                $competency = $plancompetency->get_competency();
                $description = trim(strip_tags(format_text(
                    $competency->get('description'),
                    $competency->get('descriptionformat'),
                    ['noclean' => false, 'overflowdiv' => false, 'para' => false]
                )));
                $text = trim($competency->get('shortname') . ' ' . $description);
                if ($text !== '') {
                    $texts[] = $text;
                }
            }
        }
        return $texts;
    }
}

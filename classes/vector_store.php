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
 * Caches per-course embedding vectors in the database, keyed by (courseid, model).
 *
 * Course-level data only (title/tags/summary embeddings) - never personal data, so this
 * needs no privacy provider entry. Vectors persist across cache purges and requests since
 * generating them costs a real API call; switching embedding backend/model naturally
 * invalidates old rows because they're keyed by model name.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vector_store {
    /** @var string DB table name (without prefix). */
    const TABLE = 'course_recommender_vector';

    /**
     * Get cached vectors for the given courses, embedding and storing whatever is missing.
     *
     * @param array $coursetexts Map of courseid => text to embed if not already cached.
     * @param embedder $embedder
     * @return array Map of courseid => float[] vector. Courses whose embedding failed are omitted.
     */
    public static function get_vectors(array $coursetexts, embedder $embedder): array {
        global $DB;

        if (empty($coursetexts)) {
            return [];
        }

        $model = $embedder->get_model();
        $courseids = array_keys($coursetexts);
        $vectors = [];

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
        $inparams['model'] = $model;
        $rows = $DB->get_records_select(self::TABLE, "courseid $insql AND model = :model", $inparams,
            '', 'courseid, vector');
        foreach ($rows as $row) {
            $decoded = json_decode($row->vector, true);
            if (is_array($decoded)) {
                $vectors[(int) $row->courseid] = $decoded;
            }
        }

        $missing = array_diff($courseids, array_keys($vectors));
        if (!empty($missing)) {
            $texts = [];
            foreach ($missing as $courseid) {
                $texts[$courseid] = $coursetexts[$courseid];
            }
            $newvectors = $embedder->embed(array_values($texts));
            $courseidsformissing = array_keys($texts);

            $now = time();
            foreach ($courseidsformissing as $i => $courseid) {
                $vector = $newvectors[$i] ?? null;
                if (empty($vector)) {
                    continue;
                }
                $vectors[(int) $courseid] = $vector;
                $DB->insert_record(self::TABLE, (object) [
                    'courseid' => $courseid,
                    'model' => $model,
                    'vector' => json_encode($vector),
                    'timemodified' => $now,
                ]);
            }
        }

        return $vectors;
    }
}

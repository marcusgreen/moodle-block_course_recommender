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
 * Re-ranks a course list by semantic similarity to the student's selected interest tags.
 *
 * SQL still does all the filtering (visibility, category, exact tag match) - this only
 * re-orders the resulting shortlist, so a course tagged "machine-learning" can outrank one
 * tagged "excel" for a student who picked "data-science", even without an exact tag match.
 * Failures (disabled setting, missing embedding backend, API error) are swallowed so the
 * course list always renders in its original order - ranking is an enhancement, never a
 * dependency.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reranker {

    /** @var float Weight given to the interest-tag vector when completion history is also present. */
    const INTEREST_WEIGHT = 0.7;

    /** @var float Weight given to the completed-course vector when interests are also present. */
    const COMPLETION_WEIGHT = 0.3;

    /** @var float Weight given to the interest-tag vector when blended with competency history. */
    const INTEREST_WEIGHT_WITH_COMPETENCY = 0.6;

    /** @var float Weight given to the completed-course vector when blended with competency history. */
    const COMPLETION_WEIGHT_WITH_COMPETENCY = 0.25;

    /** @var float Weight given to the competency vector when also present. */
    const COMPETENCY_WEIGHT = 0.15;

    /**
     * Re-order $courselist by cosine similarity to $selectedinterests (and, when available,
     * the student's completed-course history), when enabled.
     *
     * @param array $courselist Course entries as built by external::prepare_course_list_data().
     *  Each entry must carry an 'id' key (courseid) alongside 'title', 'summary', 'tags'.
     * @param array $selectedinterests Lower-cased interest tags the student picked.
     * @param array $completedcoursetexts Embeddable text blobs for the student's completed
     *  courses (see {@see completion_lookup::get_completed_course_texts()}), used as a
     *  secondary signal alongside the selected interests. Empty when completion-based
     *  personalisation is disabled or the student has no completions.
     * @param array $competencytexts Embeddable text blobs for the student's proficient
     *  competencies (see {@see competency_lookup::get_competency_texts()}), used as a
     *  further secondary signal. Empty when competency-based personalisation is disabled
     *  or the student has no proficient competencies.
     * @return array Same entries, re-ordered (or unchanged on any failure).
     */
    public static function rerank(
        array $courselist,
        array $selectedinterests,
        array $completedcoursetexts = [],
        array $competencytexts = []
    ): array {
        if (count($courselist) < 2
            || (empty($selectedinterests) && empty($completedcoursetexts) && empty($competencytexts))) {
            return $courselist;
        }
        if (empty(get_config('block_course_recommender', 'embedding_rerank'))) {
            return $courselist;
        }

        try {
            return self::rerank_unsafe($courselist, $selectedinterests, $completedcoursetexts, $competencytexts);
        } catch (\Throwable $e) {
            debugging('block_course_recommender similarity re-rank failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $courselist;
        }
    }

    /**
     * @param array $courselist
     * @param array $selectedinterests
     * @param array $completedcoursetexts
     * @param array $competencytexts
     * @return array
     */
    protected static function rerank_unsafe(
        array $courselist,
        array $selectedinterests,
        array $completedcoursetexts = [],
        array $competencytexts = []
    ): array {
        $embedder = new embedder();

        $coursetexts = [];
        foreach ($courselist as $course) {
            $tags = implode(' ', $course['tags'] ?? []);
            $coursetexts[$course['id']] = trim($course['title'] . ' ' . $tags . ' ' . ($course['summary'] ?? ''));
        }

        $vectors = vector_store::get_vectors($coursetexts, $embedder);
        if (empty($vectors)) {
            return $courselist;
        }

        $queryvec = self::build_query_vector($embedder, $selectedinterests, $completedcoursetexts, $competencytexts);
        if (empty($queryvec)) {
            return $courselist;
        }

        $scored = [];
        foreach ($courselist as $index => $course) {
            $vector = $vectors[$course['id']] ?? null;
            $scored[] = [
                'index' => $index,
                'score' => $vector !== null ? self::cosine_similarity($queryvec, $vector) : -1.0,
            ];
        }

        // Stable sort: ties (or courses missing a vector, score -1) keep their original order.
        usort($scored, function ($a, $b) {
            return $b['score'] <=> $a['score'] ?: $a['index'] <=> $b['index'];
        });

        $reordered = [];
        foreach ($scored as $entry) {
            $reordered[] = $courselist[$entry['index']];
        }
        return $reordered;
    }

    /**
     * Build the query vector to rank courses against: interests alone, completion history
     * alone, or a weighted blend of both when both are present.
     *
     * @param embedder $embedder
     * @param array $selectedinterests
     * @param array $completedcoursetexts
     * @param array $competencytexts
     * @return float[] Empty on embedding failure.
     */
    protected static function build_query_vector(
        embedder $embedder,
        array $selectedinterests,
        array $completedcoursetexts,
        array $competencytexts = []
    ): array {
        $interestvec = !empty($selectedinterests)
            ? $embedder->embed_single(implode(' ', $selectedinterests))
            : [];
        $completionvec = !empty($completedcoursetexts)
            ? $embedder->embed_single(implode(' ', $completedcoursetexts))
            : [];
        $competencyvec = !empty($competencytexts)
            ? $embedder->embed_single(implode(' ', $competencytexts))
            : [];

        if (!empty($competencyvec)) {
            $pairs = [];
            if (!empty($interestvec)) {
                $pairs[] = [$interestvec, self::INTEREST_WEIGHT_WITH_COMPETENCY];
            }
            if (!empty($completionvec)) {
                $pairs[] = [$completionvec, self::COMPLETION_WEIGHT_WITH_COMPETENCY];
            }
            $pairs[] = [$competencyvec, self::COMPETENCY_WEIGHT];
            return self::blend_vectors_weighted($pairs);
        }

        if (!empty($interestvec) && !empty($completionvec)) {
            return self::blend_vectors($interestvec, self::INTEREST_WEIGHT, $completionvec, self::COMPLETION_WEIGHT);
        }
        return !empty($interestvec) ? $interestvec : $completionvec;
    }

    /**
     * Elementwise weighted average of any number of vectors, renormalised so the present
     * weights sum to 1 - e.g. interests-only-and-competency (no completion history) still
     * blends sensibly instead of under-weighting the result.
     *
     * @param array $pairs Array of [vector, weight] tuples for whichever signals are present.
     * @return float[]
     */
    protected static function blend_vectors_weighted(array $pairs): array {
        $totalweight = array_sum(array_column($pairs, 1));
        if ($totalweight <= 0) {
            return [];
        }

        $len = min(...array_map(fn($pair) => count($pair[0]), $pairs));
        $out = array_fill(0, $len, 0.0);
        foreach ($pairs as [$vector, $weight]) {
            $normalisedweight = $weight / $totalweight;
            for ($i = 0; $i < $len; $i++) {
                $out[$i] += $vector[$i] * $normalisedweight;
            }
        }
        return $out;
    }

    /**
     * Elementwise weighted average of two vectors.
     *
     * @param float[] $a
     * @param float $weighta
     * @param float[] $b
     * @param float $weightb
     * @return float[]
     */
    protected static function blend_vectors(array $a, float $weighta, array $b, float $weightb): array {
        $len = min(count($a), count($b));
        $out = [];
        for ($i = 0; $i < $len; $i++) {
            $out[] = $a[$i] * $weighta + $b[$i] * $weightb;
        }
        return $out;
    }

    /**
     * @param float[] $a
     * @param float[] $b
     * @return float Similarity score between -1 and 1.
     */
    protected static function cosine_similarity(array $a, array $b): float {
        $len = min(count($a), count($b));
        if ($len === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $norma = 0.0;
        $normb = 0.0;

        for ($i = 0; $i < $len; $i++) {
            $dot   += $a[$i] * $b[$i];
            $norma += $a[$i] * $a[$i];
            $normb += $b[$i] * $b[$i];
        }

        $denom = sqrt($norma) * sqrt($normb);
        return ($denom > 0) ? ($dot / $denom) : 0.0;
    }
}

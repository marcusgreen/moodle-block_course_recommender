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
 * Generates a short "why recommended" blurb per course via the configured LLM backend.
 *
 * Personalisation is cosmetic: the prompt contains the student's selected interest tags plus
 * the title, tags and summary of each recommended course (metadata already shown on the page).
 * No name, email or other profile data is included in the prompt. When the core_ai backend is
 * used, core_ai passes the user id to the AI provider, which may forward a hashed, per-site
 * pseudonymous identifier (e.g. the OpenAI provider's "user" field).
 * Failures (disabled setting, missing AI backend, malformed response) are swallowed so the
 * course list always renders — the blurb is decoration, never a dependency.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blurb_generator {
    /** @var int Maximum number of courses to request blurbs for in one call. */
    const MAXCOURSES = 8;

    /**
     * Add a 'blurb' key to each course in $courselist, when enabled and available.
     *
     * @param array $courselist Course entries as built by external::prepare_course_list_data().
     * @param array $selectedinterests Lower-cased interest tags the student picked.
     * @return array Same list, each entry optionally carrying a 'blurb' string.
     */
    public static function annotate(array $courselist, array $selectedinterests): array {
        if (empty($courselist) || empty($selectedinterests)) {
            return $courselist;
        }
        if (empty(get_config('block_course_recommender', 'aiblurb'))) {
            return $courselist;
        }

        $subset = array_slice($courselist, 0, self::MAXCOURSES);

        try {
            $blurbs = self::request_blurbs($subset, $selectedinterests);
        } catch (\Throwable $e) {
            debugging('block_course_recommender blurb generation failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return $courselist;
        }

        foreach ($subset as $i => $course) {
            if (isset($blurbs[$i]) && is_string($blurbs[$i]) && $blurbs[$i] !== '') {
                $courselist[$i]['blurb'] = $blurbs[$i];
            }
        }

        return $courselist;
    }

    /**
     * Build the prompt, call the LLM bridge, and parse the JSON array response.
     *
     * @param array $courses Course entries (already public/display-safe data).
     * @param array $selectedinterests Lower-cased interest tags.
     * @return array Blurb strings indexed the same as $courses.
     */
    protected static function request_blurbs(array $courses, array $selectedinterests): array {
        $prompt = self::build_prompt($courses, $selectedinterests);

        $backend = (string) (get_config('block_course_recommender', 'aiblurb_backend') ?: 'core_ai_subsystem');
        $purpose = (string) (get_config('block_course_recommender', 'aiblurb_purpose') ?: 'feedback');

        $bridge = new llm_bridge(\context_system::instance()->id, 'block_course_recommender', $backend);
        $raw = $bridge->perform_request($prompt, $purpose);

        return self::extract_json_array($raw, count($courses));
    }

    /**
     * @param array $courses
     * @param array $selectedinterests
     * @return string
     */
    protected static function build_prompt(array $courses, array $selectedinterests): string {
        $interests = implode(', ', $selectedinterests);

        $lines = [];
        foreach ($courses as $i => $course) {
            $tags = implode(', ', $course['tags'] ?? []);
            $summary = $course['summary'] ?? '';
            $lines[] = "{$i}. Title: {$course['title']} | Tags: {$tags} | Summary: {$summary}";
        }
        $courseblock = implode("\n", $lines);

        return "A student selected these interests: {$interests}.\n"
            . "Here are candidate courses, one per line, numbered:\n{$courseblock}\n\n"
            . "For each course, write one short sentence (max 20 words) explaining why it matches "
            . "the student's interests, based only on the title, tags and summary given. "
            . "Return ONLY a JSON array of strings, in the same order, with no other text, "
            . "no markdown fences, and exactly " . count($courses) . " elements.";
    }

    /**
     * Parse a JSON array of strings out of the LLM response, tolerating markdown fences.
     *
     * @param string $raw
     * @param int $expectedcount
     * @return array
     */
    protected static function extract_json_array(string $raw, int $expectedcount): array {
        $raw = trim($raw);
        $raw = preg_replace('/^```(json)?/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        $raw = trim($raw);

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_slice(array_values($decoded), 0, $expectedcount);
    }
}

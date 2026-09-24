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

use advanced_testcase;

/**
 * Tests for completion_lookup.
 *
 * @package    block_course_recommender
 * @category   test
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \block_course_recommender\completion_lookup
 */
final class completion_lookup_test extends advanced_testcase {

    /**
     * Insert a {course_completions} row directly - avoids depending on the full
     * completion-tracking cron/event pipeline for what is a straight read-path test.
     *
     * @param \stdClass $course
     * @param int $userid
     * @param int $timecompleted
     * @return void
     */
    protected function mark_completed(\stdClass $course, int $userid, int $timecompleted): void {
        global $DB;
        $DB->insert_record('course_completions', (object) [
            'course' => $course->id,
            'userid' => $userid,
            'timeenrolled' => $timecompleted - DAYSECS,
            'timestarted' => $timecompleted - DAYSECS,
            'timecompleted' => $timecompleted,
            'reaggregate' => 0,
        ]);
    }

    /**
     * @covers ::get_completed_course_ids
     */
    public function test_get_completed_course_ids_excludes_incomplete_and_untracked(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $tracked = $generator->create_course(['enablecompletion' => 1]);
        $untracked = $generator->create_course(['enablecompletion' => 0]);
        $incomplete = $generator->create_course(['enablecompletion' => 1]);

        $this->mark_completed($tracked, $user->id, time());
        $this->mark_completed($untracked, $user->id, time());
        // Row exists but timecompleted = 0 (not yet completed).
        $DB->insert_record('course_completions', (object) [
            'course' => $incomplete->id,
            'userid' => $user->id,
            'timeenrolled' => time(),
            'timestarted' => time(),
            'timecompleted' => 0,
            'reaggregate' => 0,
        ]);

        $ids = completion_lookup::get_completed_course_ids($user->id);

        $this->assertEquals([(int) $tracked->id], array_map('intval', $ids));
    }

    /**
     * @covers ::get_completed_course_texts
     */
    public function test_get_completed_course_texts_includes_title_tags_summary(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course([
            'enablecompletion' => 1,
            'fullname' => 'Intro to Astronomy',
            'summary' => 'Stars and planets explained.',
        ]);
        \core_tag_tag::set_item_tags('core', 'course', $course->id, \context_course::instance($course->id), ['astronomy']);

        $this->mark_completed($course, $user->id, time());

        $texts = completion_lookup::get_completed_course_texts($user->id);

        $this->assertCount(1, $texts);
        $this->assertStringContainsString('Intro to Astronomy', $texts[0]);
        $this->assertStringContainsString('astronomy', $texts[0]);
        $this->assertStringContainsString('Stars and planets explained.', $texts[0]);
    }

    /**
     * @covers ::get_completed_course_texts
     */
    public function test_get_completed_course_texts_respects_limit_and_recency_order(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $older = $generator->create_course(['enablecompletion' => 1, 'fullname' => 'Older Course']);
        $newer = $generator->create_course(['enablecompletion' => 1, 'fullname' => 'Newer Course']);

        $this->mark_completed($older, $user->id, time() - DAYSECS);
        $this->mark_completed($newer, $user->id, time());

        $texts = completion_lookup::get_completed_course_texts($user->id, 1);

        $this->assertCount(1, $texts);
        $this->assertStringContainsString('Newer Course', $texts[0]);
    }

    /**
     * @covers ::get_completed_course_texts
     */
    public function test_get_completed_course_texts_empty_when_no_completions(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $this->assertSame([], completion_lookup::get_completed_course_texts($user->id));
    }
}

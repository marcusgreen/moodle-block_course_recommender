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
use ReflectionMethod;

/**
 * Tests for reranker.
 *
 * @package    block_course_recommender
 * @category   test
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \block_course_recommender\reranker
 */
final class reranker_test extends advanced_testcase {

    /**
     * Invoke a protected static method on reranker.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected function call(string $method, array $args) {
        $ref = new ReflectionMethod(reranker::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs(null, $args);
    }

    /**
     * @covers ::blend_vectors
     */
    public function test_blend_vectors_weighted_average(): void {
        $a = [1.0, 0.0, 0.0];
        $b = [0.0, 1.0, 0.0];

        $result = $this->call('blend_vectors', [$a, 0.7, $b, 0.3]);

        $this->assertEqualsWithDelta([0.7, 0.3, 0.0], $result, 0.0001);
    }

    /**
     * @covers ::blend_vectors
     */
    public function test_blend_vectors_truncates_to_shorter_vector(): void {
        $a = [1.0, 1.0, 1.0];
        $b = [2.0, 2.0];

        $result = $this->call('blend_vectors', [$a, 1.0, $b, 1.0]);

        $this->assertCount(2, $result);
    }

    /**
     * @covers ::build_query_vector
     */
    public function test_build_query_vector_blends_interests_and_completion(): void {
        $embedder = $this->getMockBuilder(embedder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['embed_single'])
            ->getMock();
        $embedder->method('embed_single')->willReturnMap([
            ['data science', [1.0, 0.0]],
            ['old astronomy course', [0.0, 1.0]],
        ]);

        $result = $this->call('build_query_vector', [$embedder, ['data science'], ['old astronomy course']]);

        // INTEREST_WEIGHT = 0.7, COMPLETION_WEIGHT = 0.3.
        $this->assertEqualsWithDelta([0.7, 0.3], $result, 0.0001);
    }

    /**
     * @covers ::build_query_vector
     */
    public function test_build_query_vector_interests_only(): void {
        $embedder = $this->getMockBuilder(embedder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['embed_single'])
            ->getMock();
        $embedder->expects($this->once())
            ->method('embed_single')
            ->with('data science')
            ->willReturn([1.0, 2.0]);

        $result = $this->call('build_query_vector', [$embedder, ['data science'], []]);

        $this->assertEqualsWithDelta([1.0, 2.0], $result, 0.0001);
    }

    /**
     * @covers ::build_query_vector
     */
    public function test_build_query_vector_completion_only(): void {
        $embedder = $this->getMockBuilder(embedder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['embed_single'])
            ->getMock();
        $embedder->expects($this->once())
            ->method('embed_single')
            ->with('old astronomy course')
            ->willReturn([3.0, 4.0]);

        $result = $this->call('build_query_vector', [$embedder, [], ['old astronomy course']]);

        $this->assertEqualsWithDelta([3.0, 4.0], $result, 0.0001);
    }

    /**
     * @covers ::build_query_vector
     */
    public function test_build_query_vector_falls_back_when_interest_embedding_fails(): void {
        $embedder = $this->getMockBuilder(embedder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['embed_single'])
            ->getMock();
        $embedder->method('embed_single')->willReturnMap([
            ['data science', []],
            ['old astronomy course', [3.0, 4.0]],
        ]);

        $result = $this->call('build_query_vector', [$embedder, ['data science'], ['old astronomy course']]);

        $this->assertEqualsWithDelta([3.0, 4.0], $result, 0.0001);
    }

    /**
     * @covers ::rerank
     */
    public function test_rerank_unchanged_when_fewer_than_two_courses(): void {
        $this->resetAfterTest();
        set_config('embedding_rerank', 1, 'block_course_recommender');

        $courselist = [['id' => 1, 'title' => 'Solo', 'summary' => '', 'tags' => []]];

        $this->assertSame($courselist, reranker::rerank($courselist, ['data science'], ['old course']));
    }

    /**
     * @covers ::rerank
     */
    public function test_rerank_unchanged_when_no_interests_and_no_completion(): void {
        $this->resetAfterTest();
        set_config('embedding_rerank', 1, 'block_course_recommender');

        $courselist = [
            ['id' => 1, 'title' => 'A', 'summary' => '', 'tags' => []],
            ['id' => 2, 'title' => 'B', 'summary' => '', 'tags' => []],
        ];

        $this->assertSame($courselist, reranker::rerank($courselist, [], []));
    }

    /**
     * @covers ::rerank
     */
    public function test_rerank_unchanged_when_setting_disabled(): void {
        $this->resetAfterTest();
        set_config('embedding_rerank', 0, 'block_course_recommender');

        $courselist = [
            ['id' => 1, 'title' => 'A', 'summary' => '', 'tags' => []],
            ['id' => 2, 'title' => 'B', 'summary' => '', 'tags' => []],
        ];

        $this->assertSame($courselist, reranker::rerank($courselist, ['data science'], ['old course']));
    }
}

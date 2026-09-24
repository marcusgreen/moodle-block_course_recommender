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

namespace block_course_recommender\embedding;

/**
 * Contract for an embedding backend: turns text into vectors.
 *
 * Implementations must return one vector per input text, in the same order, so course
 * vectors can be compared against a student's profile vector. All vectors compared
 * together must come from the same backend and model - changing either invalidates any
 * cached vectors.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface backend {

    /**
     * Generate embedding vectors for a list of texts.
     *
     * @param string[] $texts Texts to embed.
     * @return float[][] One vector per text, in input order.
     * @throws \moodle_exception On backend/transport failure.
     */
    public function embed(array $texts): array;

    /**
     * The embedding model this backend is configured to use.
     *
     * @return string Model identifier.
     */
    public function get_model(): string;
}

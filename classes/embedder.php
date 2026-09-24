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

use block_course_recommender\embedding\backend;
use block_course_recommender\embedding\openai_backend;
use block_course_recommender\embedding\openai_compatible_backend;
use block_course_recommender\embedding\ollama_backend;

/**
 * Facade over a pluggable embedding backend.
 *
 * The concrete backend is chosen by the 'embedding_backend' admin setting, so callers
 * (e.g. a future similarity re-ranker) stay backend-agnostic. Intentionally separate from
 * llm_bridge/core_ai: core_ai defines generate_text/generate_image/summarise_text/
 * explain_text actions but no embedding action, so this bypasses it entirely.
 *
 * Note: all embeddings compared together must come from the same backend and model.
 * Changing either invalidates any previously cached course/profile vectors.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class embedder {

    /** @var backend The active embedding backend. */
    protected backend $backend;

    /**
     * @throws \moodle_exception If the selected backend is misconfigured.
     */
    public function __construct() {
        $this->backend = self::make_backend();
    }

    /**
     * Instantiate the backend named by the 'embedding_backend' setting.
     *
     * @return backend
     * @throws \moodle_exception On unknown or misconfigured backend.
     */
    protected static function make_backend(): backend {
        $name = get_config('block_course_recommender', 'embedding_backend') ?: 'openai';
        switch ($name) {
            case 'openai_compatible':
                return new openai_compatible_backend();
            case 'ollama':
                return new ollama_backend();
            case 'openai':
            default:
                return new openai_backend();
        }
    }

    /**
     * Generate embeddings for one or more texts.
     *
     * @param string[] $texts Array of text strings to embed.
     * @return float[][] Array of embedding vectors (same order as input).
     * @throws \moodle_exception On backend failure.
     */
    public function embed(array $texts): array {
        return $this->backend->embed($texts);
    }

    /**
     * Generate embedding for a single text.
     *
     * @param string $text The text to embed.
     * @return float[] The embedding vector.
     */
    public function embed_single(string $text): array {
        $result = $this->embed([$text]);
        return $result[0] ?? [];
    }

    /**
     * The model the active backend is using.
     *
     * @return string
     */
    public function get_model(): string {
        return $this->backend->get_model();
    }
}

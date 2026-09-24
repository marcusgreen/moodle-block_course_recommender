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
 * Embeddings via the hosted OpenAI API. Requires an API key.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openai_backend extends openai_style_backend {

    /** @var string OpenAI API key. */
    protected string $apikey;

    /**
     * @throws \moodle_exception If the API key is not configured.
     */
    public function __construct() {
        $this->apikey = (string) get_config('block_course_recommender', 'embedding_openai_apikey');
        if ($this->apikey === '') {
            throw new \moodle_exception('err_noapikey', 'block_course_recommender');
        }
        $this->model = get_config('block_course_recommender', 'embedding_model') ?: 'text-embedding-3-small';
    }

    /**
     * @return string
     */
    protected function get_endpoint(): string {
        return 'https://api.openai.com/v1/embeddings';
    }

    /**
     * @return string[]
     */
    protected function get_headers(): array {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apikey,
        ];
    }
}

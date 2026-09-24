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
 * Embeddings via any service that implements the OpenAI /v1/embeddings API at a
 * configurable base URL: LocalAI, LM Studio, vLLM, Azure OpenAI, gateways, etc. The API
 * key is optional (many self-hosted servers need none).
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class openai_compatible_backend extends openai_style_backend {

    /** @var string Base URL, e.g. http://localhost:8080/v1. */
    protected string $baseurl;

    /** @var string Optional API key. */
    protected string $apikey;

    /**
     * @throws \moodle_exception If the base URL is not configured.
     */
    public function __construct() {
        $this->baseurl = trim((string) get_config('block_course_recommender', 'embedding_baseurl'));
        if ($this->baseurl === '') {
            throw new \moodle_exception('err_nobaseurl', 'block_course_recommender');
        }
        $this->apikey = (string) get_config('block_course_recommender', 'embedding_openai_apikey');
        $this->model  = get_config('block_course_recommender', 'embedding_model') ?: 'text-embedding-3-small';
    }

    /**
     * Build the endpoint from the base URL. Accepts a base with or without a trailing
     * "/embeddings" (or trailing slash) so admins can paste either.
     *
     * @return string
     */
    protected function get_endpoint(): string {
        $base = rtrim($this->baseurl, '/');
        if (substr($base, -strlen('/embeddings')) === '/embeddings') {
            return $base;
        }
        return $base . '/embeddings';
    }

    /**
     * @return string[]
     */
    protected function get_headers(): array {
        $headers = ['Content-Type: application/json'];
        if ($this->apikey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->apikey;
        }
        return $headers;
    }
}

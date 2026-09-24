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
 * Embeddings via a local (or remote) Ollama server, using its native /api/embed batch
 * endpoint. No API key required by default.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ollama_backend implements backend {

    /** @var string Base URL, e.g. http://localhost:11434. */
    protected string $baseurl;

    /** @var string Embedding model name, e.g. nomic-embed-text. */
    protected string $model;

    /**
     * @throws \moodle_exception If the base URL is not configured.
     */
    public function __construct() {
        $this->baseurl = trim((string) get_config('block_course_recommender', 'embedding_baseurl'));
        if ($this->baseurl === '') {
            throw new \moodle_exception('err_nobaseurl', 'block_course_recommender');
        }
        $this->model = get_config('block_course_recommender', 'embedding_model') ?: 'nomic-embed-text';
    }

    /**
     * @return string
     */
    public function get_model(): string {
        return $this->model;
    }

    /**
     * @param string[] $texts Texts to embed.
     * @return float[][] One vector per text, in input order.
     * @throws \moodle_exception On failure.
     */
    public function embed(array $texts): array {
        if (empty($texts)) {
            return [];
        }

        $endpoint = rtrim($this->baseurl, '/') . '/api/embed';
        $payload = json_encode([
            'model' => $this->model,
            'input' => array_values($texts),
        ]);

        $curl = new \curl();
        $curl->setopt(['CURLOPT_HTTPHEADER' => ['Content-Type: application/json']]);

        $response = $curl->post($endpoint, $payload);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        if ((int) $httpcode !== 200) {
            $errmsg = 'Ollama embeddings returned HTTP ' . $httpcode;
            $decoded = json_decode($response, true);
            if (!empty($decoded['error'])) {
                $errmsg .= ': ' . $decoded['error'];
            }
            throw new \moodle_exception('err_embeddingerror', 'block_course_recommender', '', $errmsg);
        }

        $decoded = json_decode($response, true);
        if (empty($decoded['embeddings'])) {
            throw new \moodle_exception('err_embeddingerror', 'block_course_recommender', '',
                'Empty response from Ollama embeddings.');
        }

        // Ollama returns embeddings in the same order as the input list.
        return $decoded['embeddings'];
    }
}

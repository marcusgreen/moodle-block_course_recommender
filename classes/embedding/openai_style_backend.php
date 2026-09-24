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
 * Base class for backends that speak the OpenAI /v1/embeddings request shape.
 *
 * Many services are wire-compatible with this endpoint (OpenAI, Azure OpenAI, LocalAI,
 * LM Studio, vLLM, and various gateways), so they differ only in the base URL and whether
 * an API key is required.
 *
 * @package    block_course_recommender
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class openai_style_backend implements backend {

    /** @var string Embedding model name. */
    protected string $model;

    /** @var int Maximum inputs per request. */
    protected int $batchsize = 512;

    /**
     * The full embeddings endpoint URL to POST to.
     *
     * @return string
     */
    abstract protected function get_endpoint(): string;

    /**
     * HTTP headers for the request (e.g. auth), including Content-Type.
     *
     * @return string[]
     */
    abstract protected function get_headers(): array;

    /**
     * @return string Model identifier.
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
        $all = [];
        foreach (array_chunk($texts, $this->batchsize) as $batch) {
            foreach ($this->call_api($batch) as $vector) {
                $all[] = $vector;
            }
        }
        return $all;
    }

    /**
     * POST one batch to the endpoint and return its vectors in input order.
     *
     * @param string[] $texts Batch of texts.
     * @return float[][] Embedding vectors.
     * @throws \moodle_exception On failure.
     */
    protected function call_api(array $texts): array {
        $payload = json_encode([
            'model' => $this->model,
            'input' => array_values($texts),
        ]);

        $curl = new \curl();
        $curl->setopt(['CURLOPT_HTTPHEADER' => $this->get_headers()]);

        $response = $curl->post($this->get_endpoint(), $payload);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        if ((int) $httpcode !== 200) {
            $errmsg = 'Embeddings API returned HTTP ' . $httpcode;
            $decoded = json_decode($response, true);
            if (!empty($decoded['error']['message'])) {
                $errmsg .= ': ' . $decoded['error']['message'];
            }
            throw new \moodle_exception('err_embeddingerror', 'block_course_recommender', '', $errmsg);
        }

        $decoded = json_decode($response, true);
        if (empty($decoded['data'])) {
            throw new \moodle_exception('err_embeddingerror', 'block_course_recommender', '',
                'Empty response from embeddings API.');
        }

        // Sort by index to guarantee order matches input.
        usort($decoded['data'], fn($a, $b) => $a['index'] <=> $b['index']);

        return array_map(fn($item) => $item['embedding'], $decoded['data']);
    }
}

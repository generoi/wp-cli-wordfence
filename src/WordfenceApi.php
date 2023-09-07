<?php

namespace GeneroWP\WpCliWordfence;

use Generator;
use GeneroWP\WpCliWordfence\Models\Record;

/**
 * @see https://www.wordfence.com/intelligence-documentation/v2-accessing-and-consuming-the-vulnerability-data-feed/
 */
class WordfenceApi
{
    const WORDFENCE_SCANNER_ENDPOINT = 'https://www.wordfence.com/api/intelligence/v2/vulnerabilities/scanner';

    protected string $endpoint;

    /**
     * @param array<string,mixed> $headers
     */
    public function __construct(
        protected array $headers = [],
    ) {
        $this->endpoint = self::WORDFENCE_SCANNER_ENDPOINT;
    }

    /**
     * @return Generator|Record[]
     */
    public function getKnownVulnerabilities(): Generator
    {
        $result = $this->getResponse();
        if (is_null($result)) {
            return;
        }
        foreach ($result as $data) {
            yield Record::fromRecord($data);
        }
    }

    /**
     * @throws ApiException
     * @return null|array<string,mixed>
     */
    protected function getResponse(): ?array
    {
        $response = wp_remote_get(self::WORDFENCE_SCANNER_ENDPOINT, [
            'timeout' => 10,
            'headers' => array_merge([
                'Accept' => 'application/json',
            ], $this->headers),
        ]);

        if (is_wp_error($response)) {
            throw new ApiException($response->get_error_message());
        }

        $reponseCode = (int) wp_remote_retrieve_response_code($response);
        switch ($reponseCode) {
            case 200: // OK
                $result = json_decode(wp_remote_retrieve_body($response), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new ApiException(sprintf('JSON error: %s', json_last_error_msg()));
                }

                return $result;
            case 304: // Not modified
                return null;
            default:
                throw new ApiException(sprintf('Unknown response code: %s', $reponseCode));
        }
    }
}

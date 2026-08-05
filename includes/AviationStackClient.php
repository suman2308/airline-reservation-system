<?php
/**
 * AeroBook – Aviationstack HTTP Client
 *
 * Handles all HTTP communication with the Aviationstack API.
 * Implements cURL-based requests with timeout, error handling,
 * and automatic pagination support.
 *
 * Never exposes the API key to logs, HTML, or JavaScript.
 */

class AviationStackClient {
    private $apiKey;
    private $baseUrl = 'https://api.aviationstack.com/v1/';
    private $timeout = 15;
    private $maxRetries = 2;

    public function __construct($apiKey = null) {
        if ($apiKey === null) {
            $apiKey = defined('AVIATIONSTACK_API_KEY') ? AVIATIONSTACK_API_KEY : '';
        }
        $this->apiKey = $apiKey;
    }

    /**
     * Test connectivity to the Aviationstack API.
     * Returns ['success' => true] or ['success' => false, 'error' => '...'].
     */
    public function testConnection() {
        return $this->get('airports', ['limit' => 1]);
    }

    /**
     * Fetch data from a given endpoint with optional parameters.
     * Automatically paginates through all available pages.
     *
     * @param string $endpoint e.g. 'airports', 'airlines', 'flights'
     * @param array $params Query parameters
     * @param bool $paginate Whether to fetch ALL pages (default true)
     * @return array ['success' => bool, 'data' => [...], 'error' => '...', 'total' => int]
     */
    public function get($endpoint, $params = [], $paginate = true) {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Aviationstack API key is not configured. Set AVIATIONSTACK_API_KEY in .env.',
                'data' => [],
                'total' => 0,
            ];
        }

        $params['access_key'] = $this->apiKey;
        $allData = [];
        $total = 0;
        $offset = 0;
        $limit = 100; // Max per page

        $retryCount = 0;

        do {
            if ($offset > 0) {
                $params['offset'] = $offset;
            }
            if (!isset($params['limit'])) {
                $params['limit'] = $limit;
            }

            $result = $this->request($endpoint, $params);

            if (!$result['success']) {
                // If this is the first request and it failed, return error immediately
                if ($offset === 0) {
                    return $result;
                }
                // If a subsequent page fails, return what we have with a warning
                break;
            }

            $data = $result['data'] ?? [];
            $allData = array_merge($allData, $data);
            $total = $result['total'] ?? count($data);
            $offset += $limit;

            // Stop if we've fetched all records or hit free plan limit (10000)
            if (!$paginate || $offset >= $total || $offset >= 10000) {
                break;
            }

        } while (count($data) > 0);

        return [
            'success' => true,
            'data' => $allData,
            'total' => $total,
            'fetched' => count($allData),
        ];
    }

    /**
     * Perform a single HTTP request to the Aviationstack API.
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    private function request($endpoint, $params) {
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'AeroBook/1.0',
        ];

        // Use a bundled CA bundle when the PHP runtime has none configured
        // (e.g. Windows builds). Falls back to the system default otherwise.
        $caFile = $this->findCaBundle();
        if ($caFile !== null) {
            $options[CURLOPT_CAINFO] = $caFile;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }

        // Handle cURL errors
        if ($response === false) {
            logError('Aviationstack cURL error', [
                'endpoint' => $endpoint,
                'error' => $curlError,
            ]);
            return [
                'success' => false,
                'error' => 'HTTP request failed: ' . $curlError,
                'data' => [],
                'total' => 0,
            ];
        }

        // Handle HTTP errors
        if ($httpCode !== 200) {
            $errorMsg = "HTTP {$httpCode}";
            // Try to extract error from response body
            $body = json_decode($response, true);
            if (isset($body['error']['message'])) {
                $errorMsg .= ': ' . $body['error']['message'];
            } elseif (isset($body['error']['info'])) {
                $errorMsg .= ': ' . $body['error']['info'];
            }

            logError('Aviationstack HTTP error', [
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'error' => $errorMsg,
            ]);
            return [
                'success' => false,
                'error' => $errorMsg,
                'data' => [],
                'total' => 0,
            ];
        }

        // Parse JSON
        $body = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logError('Aviationstack JSON parse error', [
                'endpoint' => $endpoint,
                'error' => json_last_error_msg(),
            ]);
            return [
                'success' => false,
                'error' => 'Invalid JSON response from API.',
                'data' => [],
                'total' => 0,
            ];
        }

        // Check for API-level errors
        if (isset($body['error'])) {
            $errorMsg = $body['error']['message'] ?? $body['error']['info'] ?? 'Unknown API error';
            logError('Aviationstack API error', [
                'endpoint' => $endpoint,
                'error' => $errorMsg,
            ]);
            return [
                'success' => false,
                'error' => $errorMsg,
                'data' => [],
                'total' => 0,
            ];
        }

        $pagination = $body['pagination'] ?? [];
        return [
            'success' => true,
            'data' => $body['data'] ?? [],
            'total' => $pagination['total'] ?? 0,
        ];
    }

    /**
     * Check if the API key is configured.
     */
    public function isConfigured() {
        return !empty($this->apiKey);
    }

    /**
     * Locate a CA certificate bundle for cURL.
     *
     * Priority: system-configured curl.cainfo / openssl.cafile, then the
     * project-bundled includes/cacert.pem. Returns null to let cURL fall
     * back to its compiled-in defaults.
     */
    private function findCaBundle() {
        static $cached = null;
        if ($cached !== null) return $cached;

        $configured = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
        if ($configured && is_file($configured)) {
            return $cached = $configured;
        }

        $bundled = __DIR__ . '/cacert.pem';
        if (is_file($bundled)) {
            return $cached = $bundled;
        }

        return $cached = false; // let cURL use defaults
    }
}

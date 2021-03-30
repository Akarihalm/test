<?php

namespace App\Utils;

use GuzzleHttp\Client;

/**
 * Class ApiClient
 * @package App\Utils
 */

class ApiClientManager
{
    private $client;
    private $defaultHeader = [
        'Content-Type' => 'application/json'
    ];

    const TIMEOUT_SECONDS = 10.0;

    /**
     * ApiClient constructor.
     * @param $url
     */
    public function __construct($url)
    {
        $this->client = new Client([
            'base_uri' => $url,
            'timeout'  => self::TIMEOUT_SECONDS,
            'verify'   => false
        ]);
    }

    /**
     * @param array $header
     */
    public function setDefaultHeader($header = [])
    {
        $this->defaultHeader = $header;
    }

    /**
     * @param array $header
     * @return array
     */
    private function formatHeader($header = [])
    {
        return array_merge($this->defaultHeader, $header);
    }

    /**
     * HTTP HET REQUEST
     *
     * @param string $path
     * @param array $query
     * @param array $header
     * @return mixed
     */
    public function get($path = '', $query = [], $header = [])
    {
        $response = $this->client->get($path, [
            'query' => $query,
            'headers' => $this->formatHeader($header)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * HTTP POST REQUEST
     *
     * @param string $path
     * @param array $body
     * @param array $header
     * @return mixed
     */
    public function post($path = '', $body = [], $header = [])
    {
        $response = $this->client->post($path, [
            'body' => json_encode($body),
            'headers' => $this->formatHeader($header)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * HTTP DELETE REQUEST
     *
     * @param string $path
     * @param array $body
     * @param array $header
     * @return mixed
     */
    public function delete($path = '', $body = [], $header = [])
    {
        $response = $this->client->delete($path, [
            'body' => json_encode($body),
            'headers' => $this->formatHeader($header)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * HTTP POST REQUEST
     *
     * @param string $path
     * @param array $body
     * @param array $header
     * @return mixed
     */
    public function put($path = '', $body = [], $header = [])
    {
        $response = $this->client->put($path, [
            'body' => json_encode($body),
            'headers' => $this->formatHeader($header)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * HTTP HEAD REQUEST
     *
     * @param string $path
     * @param array $body
     * @param array $header
     * @return mixed
     */
    public function head($path = '', $body = [], $header = [])
    {
        $response = $this->client->head($path, [
            'body' => json_encode($body),
            'headers' => $this->formatHeader($header)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $path
     * @param $options
     * @return mixed
     */
    public function postRaw($path, $options)
    {
        $response = $this->client->post($path, $options);

        return json_decode($response->getBody()->getContents(), true);
    }
}

<?php

namespace InfluxDB2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class DefaultApi
{
    const DEFAULT_TIMEOUT = 10;
    public $options;
    /** @var Client */
    public $http;

    /**
     * DefaultApi constructor.
     * @param $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;

        $this->http = new Client([
            'base_uri' => $this->options["url"],
            'timeout' => self::DEFAULT_TIMEOUT
        ]);
    }

    /**
     * @param $payload
     * @param $uriPath
     * @param $queryParams
     * @param int $timeout - Float describing the timeout of the request in seconds. Use 0 to wait indefinitely (the default behavior).
     * @param bool $stream - use streaming
     * @return ResponseInterface
     */
    public function post($payload, $uriPath, $queryParams, $timeout = self::DEFAULT_TIMEOUT, bool $stream = false): ResponseInterface
    {
        return $this->request($payload, $uriPath, $queryParams, 'POST', $timeout, $stream);
    }

    public function get($payload, $uriPath, $queryParams, $timeout = self::DEFAULT_TIMEOUT): ResponseInterface
    {
        return $this->request($payload, $uriPath, $queryParams, 'GET', $timeout, false);
    }

    private function request($payload, $uriPath, $queryParams, $method, $timeout = self::DEFAULT_TIMEOUT, bool $stream = false): ResponseInterface
    {
        try {
            $options = [
                'headers' => [
                    'Authorization' => "Token {$this->options['token']}",
                    'User-Agent' => 'influxdb-client-php/' . \InfluxDB2\Client::VERSION,
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams,
                'body' => $payload,
                'stream' => $stream,
                'timout' => $timeout
            ];

            // enable debug
            if (array_key_exists("debug", $this->options)) {
                $options['debug'] = $this->options["debug"];
            }

            //execute post call
            $response = $this->http->request($method, $uriPath, $options);

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        $uriPath
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    $response->getBody()
                );
            }
            return $response;

        } catch (RequestException $e) {
            throw new ApiException(
                "[{$e->getCode()}] {$e->getMessage()}",
                $e->getCode(),
                $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
            );
        }
    }

    function check($key, $value)
    {
        if ((!isset($value) || trim($value) === '')) {
            $options = implode(', ', array_map(
                function ($v, $k) {
                    if(is_array($v)){
                        return $k.'[]='.implode('&'.$k.'[]=', $v);
                    }else{
                        return $k.'='.$v;
                    }
                },
                $this->options,
                array_keys($this->options)
            ));
            throw new InvalidArgumentException("The '${key}' should be defined as argument or default option: {$options}");
        }
    }
}


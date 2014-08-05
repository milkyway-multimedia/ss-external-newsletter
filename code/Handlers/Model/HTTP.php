<?php namespace Milkyway\SS\MailchimpSync\Handlers\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;

/**
 * Milkyway Multimedia
 * HTTP.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
abstract class HTTP {
    const API_VERSION = '2.0';

    protected $endpoint = 'https://<dc>.api.mailchimp.com/';
    protected $apiKey;

    protected $cacheLifetime = 0;
    protected $method = 'post';

    protected $client;
    protected $cache;

    public function __construct($apiKey, $cache = 0) {
        $this->apiKey = $apiKey;
        $this->cacheLifetime = $cache;
    }

    public function cleanCache() {
        // $this->cache()->clean();
    }

    /**
     * Get a new HTTP client instance.
     * @return \GuzzleHttp\Client
     */
    protected function http()
    {
        if(!$this->client)
            $this->client = new Client($this->getHttpSettings());

        return $this->client;
    }

    protected function getHttpSettings() {
        return [
            'base_url' => $this->endpoint(),
        ];
    }

    protected function isError(ResponseInterface $response) {
        return ($response->getStatusCode() < 200 || $response->getStatusCode() > 399);
    }

    protected function cache() {
        if(!$this->cache)
            $this->cache = \SS_Cache::factory('MailchimpSync_Handlers', 'Output', ['lifetime' => $this->cacheLifetime * 60 * 60]);

        return $this->cache;
    }

    protected function getCacheKey(array $vars = []) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', get_class($this) . '_' . urldecode(http_build_query($vars, '', '_')));
    }

    protected function results($url, $params = []) {
        $cacheKey = $this->getCacheKey($params);

        if(!($body = unserialize($this->cache()->load($cacheKey)))) {
            $params = array_merge($this->params(), $params);

            if($this->method != 'get')
                $params = ['body' => $params];

            try {
                $response = $this->http()->{$this->method}(
                    $url,
                    $params
                );
            } catch(RequestException $e) {
                if(($response = $e->getResponse()) && $body = $this->parseResponse($response)) {
                    throw new HTTP_Exception($response, isset($body['name']) ? $body['name'] : '', isset($body['code']) ? $body['code'] : 400);
                }
            }

            if($response && !$this->isError($response)) {
                $body = $this->parseResponse($response);

                if(!$this->isValid($body))
                    throw new HTTP_Exception($response, sprintf('Data not received from %s. Please check your credentials.', $this->endpoint()));

                $this->cache()->save(serialize($body), $cacheKey);

                return $body;
            }
        }

        return $body;
    }

    protected function parseResponse(ResponseInterface $response) {
        return $response->json();
    }

    protected function isValid($body) {
        return true;
    }

    protected function endpoint($action = '', $dataCentre = '')
    {
        if(!$dataCentre && $this->apiKey) {
            $parts = explode('-', $this->apiKey);
            $dataCentre = array_pop($parts);
        }

        return str_replace('<dc>', $dataCentre, \Controller::join_links($this->endpoint, static::API_VERSION, $action));
    }

    protected function params() {
        return [
            'apikey' => $this->apiKey,
        ];
    }
}

class HTTP_Exception extends \Exception {
    public $response;

    public function __construct($response = null, $message = null, $statusCode = null) {
        parent::__construct($message, $statusCode);
        $this->response = $response;
    }
}
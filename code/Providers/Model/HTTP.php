<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Model;

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
    protected $apiKey;

    protected $cacheLifetime = 1;
    protected $method = 'post';

    protected $client;

	// A cache is created for each action, so easy to remove for one specific action...
    protected $caches = [];

    public function __construct($apiKey, $cache = 1) {
        $this->apiKey = $apiKey;
        $this->cacheLifetime = $cache;
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

    protected function cache($key = '') {
	    $key = $key ? preg_replace('/[^a-zA-Z0-9_]/', '', get_class($this) . '_' . $key) : preg_replace('/[^a-zA-Z0-9_]/', '', get_class($this));

	    if(!isset($this->caches[$key]))
            $this->caches[$key] = \SS_Cache::factory($key, 'Output', ['lifetime' => $this->cacheLifetime * 60 * 60]);

        return $this->caches[$key];
    }

	public function cleanCache($key = '') {
		if($key) {
			$key = $key ? str_replace('\\', '', get_class($this)) . '_' . str_replace('/', '', $key) : str_replace('\\', '', get_class($this));

			if(isset($this->caches[$key]))
				$this->caches[$key]->clean();
		}
		else {
			foreach($this->caches as $cache)
				$cache->clean();
		}
	}

    protected function getCacheKey(array $vars = []) {
        return preg_replace('/[^a-zA-Z0-9_]/', '', get_class($this) . '_' . urldecode(http_build_query($vars, '', '_')));
    }

    protected function results($action, $params = []) {
        $cacheKey = $this->getCacheKey($params);

        if(!$this->cacheLifetime || !($body = unserialize($this->cache($action)->load($cacheKey)))) {
            $params = array_merge($this->params(), $params);

            if($this->method != 'get')
                $params = ['body' => $params];

            try {
                $response = $this->http()->{$this->method}(
                    $this->endpoint($action),
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

                $this->cache($action)->save(serialize($body), $cacheKey);

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

    protected function params() {
        return [
            'apikey' => $this->apiKey,
        ];
    }

	abstract protected function endpoint($action = '');
}

class HTTP_Exception extends \Exception {
    public $response;

    public function __construct($response = null, $message = null, $statusCode = null) {
        parent::__construct($message, $statusCode);
        $this->response = $response;
    }
}
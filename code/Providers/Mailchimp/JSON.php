<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;
/**
 * Milkyway Multimedia
 * JSON.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use Milkyway\SS\ExternalNewsletter\Handlers\Model\HTTP;
use Milkyway\SS\ExternalNewsletter\Utilities;

abstract class JSON extends HTTP {
	const API_VERSION = '2.0';

	protected $endpoint = 'https://<dc>.api.mailchimp.com/';

	protected function endpoint($action = '')
	{
		if($this->apiKey) {
			$parts = explode('-', $this->apiKey);
			$dataCentre = array_pop($parts);
		}
		else
			$dataCentre = Utilities::env_value('DataCentre');

		return str_replace('<dc>', $dataCentre, \Controller::join_links($this->endpoint, static::API_VERSION, $action, '.json'));
	}
} 
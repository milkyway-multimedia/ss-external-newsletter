<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;
/**
 * Milkyway Multimedia
 * Template.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Template extends JSON implements \Milkyway\SS\ExternalNewsletter\Contracts\Template {
	public function get($params = []) {
		$response = $this->results('templates/list', $params);

		$templates = [];

		if(isset($response['user']) && count($response['user']))
			$templates = array_merge_recursive($templates, $response['user']);
		if(isset($response['gallery']) && count($response['gallery']))
			$templates = array_merge_recursive($templates, $response['gallery']);

		return $templates;
	}
} 
<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;

/**
 * Milkyway Multimedia
 * Config.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Config implements \Milkyway\SS\ExternalNewsletter\Contracts\Config
{
	public function prefix()
	{
		return 'Mailchimp';
	}

	public function map()
	{
		return [
			'APIKey'               => 'api_key',

			'DoubleOptIn'          => 'double_opt_in',

			'DefaultLists'         => 'default_lists',
			'DefaultParams'        => 'default_params',
			'DefaultGroups'        => 'default_groups',

			'Subscribe_OnWrite'    => 'subscribe_on_write',
			'Unsubscribe_OnDelete' => 'unsubscribe_on_delete',

			'AllowedLists'         => 'allowed_lists',
		];
	}
}
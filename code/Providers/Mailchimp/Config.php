<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;

/**
 * Milkyway Multimedia
 * Config.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Config extends \Milkyway\SS\ExternalNewsletter\Providers\Model\Config
{
	public function prefix()
	{
		return 'Mailchimp';
	}
}
<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;

/**
 * Milkyway Multimedia
 * Config.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use Object;
use Milkyway\SS\ExternalNewsletter\Providers\Model\Driver as Contract;

class Driver extends Contract
{
	public function __construct() {
		$this->services = [
			'campaign' => Object::create('Milkyway\SS\ExternalNewsletter\Providers\Mailchimp\Campaign', [$this]),
			'campaign_manager' => Object::create('Milkyway\SS\ExternalNewsletter\Providers\Mailchimp\CampaignManager', [$this]),
			'lists' => Object::create('Milkyway\SS\ExternalNewsletter\Providers\Mailchimp\Lists', [$this]),
			'subscriber' => Object::create('Milkyway\SS\ExternalNewsletter\Providers\Mailchimp\Subscriber', [$this]),
			'subscriber_manager' => Object::create('Milkyway\SS\ExternalNewsletter\Providers\Mailchimp\SubscriberManager', [$this]),
			'template' => Object::create('Milkyway\SS\ExternalNewsletter\Providers\Mailchimp\Template', [$this]),
		];
	}

	public function prefix() {
		return 'Mailchimp';
	}

	public function title() {
		return _t('ExternalNewsletter.MAILCHIMP', 'Mailchimp');
	}
}
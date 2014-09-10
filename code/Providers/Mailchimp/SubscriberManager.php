<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;

use Milkyway\SS\ExternalNewsletter\Utilities;

/**
 * Milkyway Multimedia
 * SubscriberManager.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class SubscriberManager implements \Milkyway\SS\ExternalNewsletter\Contracts\SubscriberManager {
	public function applyExternalVars(\ViewableData $subscriber, array $data = []) {
		if(isset($data['merges']) && $subscriber->MailchimpMergeVars) {
			foreach($subscriber->MailchimpMergeVars as $db => $var) {
				if(isset($data['merges'][$var]))
					$subscriber->$db = $data['merges'][$var];
			}
		}
	}

	public function subscribe(\Milkyway\SS\ExternalNewsletter\Contracts\Subscriber $handler, \ViewableData $subscriber, $params = []) {
		if(isset($params['list']) || $subscriber->SubcriberListID || $subscriber->ExtListId || $subscriber->MailchimpListID) {
			$email = $handler->subscribe(
				array_merge([
					'list' => $this->getListIdFromRecord($subscriber),
					'groups' => $subscriber->MailchimpInterestGroups,
					'double_optin' => Utilities::env_value('DoubleOptIn', $subscriber),
				], $params), $subscriber->MailchimpListParams
			);

			if(isset($email['euid']))
				$subscriber->EUId = $email['euid'];

			return $email;
		}

		return [];
	}

	public function unsubscribe(\Milkyway\SS\ExternalNewsletter\Contracts\Subscriber $handler, \ViewableData $subscriber, $params = []) {
		if(isset($params['list']) || $subscriber->SubcriberListID || $subscriber->ExtListId || $subscriber->MailchimpListID) {
			$handler->unsubscribe(
				array_merge([
					'list' => $this->getListIdFromRecord($subscriber),
				], $params), $subscriber->MailchimpListParams
			);
		}
	}

	protected function getListIdFromRecord($subscriber) {
		return $subscriber->SubcriberListID ? $subscriber->SubcriberListID : $subscriber->MailchimpListID ? $subscriber->MailchimpListID : $subscriber->ExtListId;
	}
} 
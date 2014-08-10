<?php
/**
 * Milkyway Multimedia
 * SubscriberManager.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter\Contracts;

interface SubscriberManager {
	public function applyExternalVars(\ViewableData $subscriber, array $data = []);

	public function subscribe(Subscriber $handler, \ViewableData $subscriber, $params = []);
	public function unsubscribe(Subscriber $handler, \ViewableData $subscriber, $params = []);
	// public function update(Subscriber $handler, \ViewableData $subscriber, $params = []);
} 
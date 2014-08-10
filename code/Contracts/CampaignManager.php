<?php
/**
 * Milkyway Multimedia
 * CampaignManager.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter\Contracts;

interface CampaignManager {
	public function postVars(\ExtSendLog $campaign);

	public function create(Campaign $handler, \ExtSendLog $campaign);
	public function delete(Campaign $handler, \ExtSendLog $campaign);
	public function update(Campaign $handler, \ExtSendLog $campaign);
} 
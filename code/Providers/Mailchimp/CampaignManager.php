<?php
/**
 * Milkyway Multimedia
 * CampaignManager.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;


use Milkyway\SS\ExternalNewsletter\Contracts\Campaign;
use Milkyway\SS\ExternalNewsletter\Handlers\Model\HTTP_Exception;

class CampaignManager implements \Milkyway\SS\ExternalNewsletter\Contracts\CampaignManager {
	public function postVars(\ExtSendLog $campaign) {
		$vars['type'] = 'regular';

		$vars['options']['subject'] = $campaign->Campaign()->Subject;
		$vars['options']['title'] = $campaign->Campaign()->Subject;

		$vars['options']['from_email'] = $campaign->Campaign()->From;
		$vars['options']['from_name'] = $campaign->Campaign()->FromName;

		$vars['options']['auto_footer'] = false;
		$vars['options']['inline_css'] = true;
		$vars['options']['generate_text'] = true;

		$vars['content']['html'] = $campaign->Campaign()->Content;
		$vars['options']['list_id'] = $campaign->ExtId;

		$campaign->extend('updatePostVars', $vars);

		return $vars;
	}

	public function create(Campaign $handler, \ExtSendLog $campaign) {
		$data = $handler->create(
			$this->postVars($campaign)
		);

		if (isset($data['id']))
			$campaign->ExtId = $data['id'];
		elseif (isset($data['web_id']))
			$campaign->ExtSlug = $data['web_id'];
	}

	public function delete(Campaign $handler, \ExtSendLog $campaign) {
		$handler->delete([
			'cid' => $campaign->ExtId
		]);
	}

	private $updated = false;
	private $attemptFix = false;

	public function update(Campaign $handler, \ExtSendLog $campaign) {
		if(!$this->updated) {
			$vars = $this->postVars($campaign);

			foreach ($vars as $type => $var) {
				if (!is_array($var)) {
					continue;
				}

				try {
					$data = $handler->update(
						[
							'cid'   => $campaign->ExtId,
							'name'  => $type,
							'value' => $var,
						]
					);
				} catch (HTTP_Exception $e) {
					if (!$this->attemptFix && $e->getMessage() == 'Campaign_DoesNotExist') {
						$this->attemptFix = true;

						$campaign->ExtId = null;
						$this->create($handler, $campaign);
						$campaign->write();
					}
				}

				if (isset($data['status']))
					$campaign->Status = $data['status'];
				elseif (isset($data['emails_sent']))
					$campaign->NumberSent = $data['emails_sent'];
			}

			$this->attemptFix = false;
			$this->updated = true;
		}
	}
} 
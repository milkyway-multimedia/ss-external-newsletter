<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;
/**
 * Milkyway Multimedia
 * Campaign.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Campaign extends JSON implements \Milkyway\SS\ExternalNewsletter\Contracts\Campaign {
    public function create($params = []) {
        $results = $this->results('campaigns/create', $params);
	    $this->cleanCampaignCache();
        return $results;
    }

    public function update($params = []) {
        $results = $this->results('campaigns/update', $params);
	    $this->cleanCampaignCache();
        return $results;
    }

    public function delete($params = []) {
        $results = $this->results('campaigns/delete', $params);
	    $this->cleanCampaignCache();
        return $results;
    }

    public function send($args = [], $params = []) {

    }

    public function schedule($args = [], $params = []) {

    }

    public function test($args = [], $params = []) {
        if(isset($args['email']) && !is_array($args['email']))
            $params['test_emails'] = [$args['email']];

        $results = $this->results('campaigns/send-test', $params);
        $this->cleanCampaignCache();
        return $results;
    }

	protected function cleanCampaignCache() {
		$this->cleanCache('campaigns/content');
		$this->cleanCache('campaigns/list');
	}
} 
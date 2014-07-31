<?php
/**
 * Milkyway Multimedia
 * SendCampaign.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\MailchimpSync\Handlers;


use Milkyway\SS\MailchimpSync\Handlers\Model\HTTP;

class Campaign extends HTTP {
    public function create($params = []) {
        $results = $this->results($this->endpoint('campaigns/create.json'), $params);
        $this->cleanCache();
        return $results;
    }

    public function update($params = []) {
        $results = $this->results($this->endpoint('campaigns/update.json'), $params);
        $this->cleanCache();
        return $results;
    }

    public function delete($params = []) {
        $results = $this->results($this->endpoint('campaigns/delete.json'), $params);
        $this->cleanCache();
        return $results;
    }

    public function send($args = [], $params = []) {

    }

    public function schedule($args = [], $params = []) {

    }

    public function test($args = [], $params = []) {
        if(isset($args['email']) && !is_array($args['email']))
            $params['test_emails'] = [$args['email']];

        $results = $this->results($this->endpoint('campaigns/send-test.json'), $params);
        $this->cleanCache();
        return $results;
    }
} 
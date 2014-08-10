<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;
/**
 * Milkyway Multimedia
 * SubscriberList.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Lists extends JSON implements \Milkyway\SS\ExternalNewsletter\Contracts\Lists {
    public function get($params = []) {
        $response = $this->results('lists/list', $params);

        if(isset($response['data']))
            return $response['data'];

        return [];
    }
} 
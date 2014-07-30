<?php
/**
 * Milkyway Multimedia
 * SubscriberList.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\MailchimpSync\Handlers;

use Milkyway\SS\MailchimpSync\Handlers\Model\HTTP;

class Lists extends HTTP {
    public function get($filters = []) {
        $params = count($filters) ? ['filters' => $filters] : [];
        $response = $this->results($this->endpoint('lists/list.json'), $params);

        if(isset($response['data']))
            return $response['data'];

        return [];
    }
} 
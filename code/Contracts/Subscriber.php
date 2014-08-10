<?php namespace Milkyway\SS\ExternalNewsletter\Contracts;

/**
 * Milkyway Multimedia
 * Subscriber.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

interface Subscriber {
    public function get($params = []);
    public function one($params = []);

    public function subscribe($args = [], $params = []);
    public function unsubscribe($args = [], $params = []);
}
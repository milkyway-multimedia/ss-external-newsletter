<?php namespace Milkyway\SS\ExternalNewsletter\Contracts;
/**
 * Milkyway Multimedia
 * SendCampaign.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

interface Campaign {
    public function create($params = []);
    public function update($params = []);
    public function delete($params = []);
    public function send($args = [], $params = []);
    public function schedule($args = [], $params = []);
    public function test($args = [], $params = []);
}
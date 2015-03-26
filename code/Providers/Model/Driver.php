<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Model;

/**
 * Milkyway Multimedia
 * Driver.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use LogicException;
use Milkyway\SS\ExternalNewsletter\Contracts\Driver as Contract;

abstract class Driver implements Contract
{
    protected $services = [];

    public function map()
    {
        return [
            'APIKey'               => 'api_key',
            'DoubleOptIn'          => 'double_opt_in',
            'AllowedLists'         => 'allowed_lists',
            'AllowedGroups'        => 'allowed_groups',
            'DefaultLists'         => 'default_lists',
            'DefaultParams'        => 'default_params',
            'DefaultGroups'        => 'default_groups',
            'NoListSync'           => 'no_list_sync',
            'GlobalSubscribeForm'  => 'global_subscribe_form',
        ];
    }

    public function service($name = '') {
        if(!$name)
            return $this->services;

        if(isset($this->services[$name]))
            return $this->services[$name];

        throw new LogicException(sprintf('%s does not support the service: ' . $name));
    }
} 
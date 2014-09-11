<?php namespace Milkyway\SS\ExternalNewsletter\Providers\Model;

/**
 * Milkyway Multimedia
 * Config.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

abstract class Config implements \Milkyway\SS\ExternalNewsletter\Contracts\Config
{
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
            'GlobalSubscribeForm'           => 'global_subscribe_form',
        ];
    }
} 
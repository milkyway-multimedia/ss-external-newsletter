<?php
/**
 * Milkyway Multimedia
 * Utilities.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\MailchimpSync;


class Utilities {
    public static $environment = [];

    protected static $environment_mapping = [
        'Mailchimp_APIKey' => 'mailchimp_api_key',

        'Mailchimp_DoubleOptIn' => 'mailchimp_double_opt_in',

        'Mailchimp_DefaultLists' => 'mailchimp_default_lists',
        'Mailchimp_DefaultParams' => 'mailchimp_default_params',
        'Mailchimp_DefaultGroups' => 'mailchimp_default_groups',

        'Mailchimp_Subscribe_OnWrite' => 'mailchimp_subscribe_on_write',
        'Mailchimp_Unsubscribe_OnDelete' => 'mailchimp_unsubscribe_on_delete',
    ];

    public static function config() {
        return \Config::inst()->forClass('Mailchimp');
    }

    public static function env_value($setting, \ViewableData $object = null) {
        if($object && $object->$setting)
            return $object->$setting;

        if(isset(self::$environment[$setting]))
            return self::$environment[$setting];

        $value = null;

        if(isset(self::$environment_mapping[$setting])) {
            $dbSetting = $setting;
            $setting = self::$environment_mapping[$setting];

            if($object && $object->config()->$setting)
                $value = $object->config()->$setting;

            if (!$value) {
                $pos = strpos($setting,'mailchimp_');
                $simpleSetting = ($pos === 0) ? substr_replace($setting,'',0,strlen($setting)) : $setting;
                $value = static::config()->$simpleSetting;
            }

            if (!$value && \ClassInfo::exists('SiteConfig')) {
                if (\SiteConfig::current_site_config()->$dbSetting) {
                    $value = \SiteConfig::current_site_config()->$dbSetting;
                } elseif (\SiteConfig::config()->$setting) {
                    $value = \SiteConfig::config()->$setting;
                }
            }

            if (!$value) {
                if (getenv($setting)) {
                    $value = getenv($setting);
                } elseif (isset($_ENV[$setting])) {
                    $value = $_ENV[$setting];
                }
            }

            if ($value) {
                self::$environment[$setting] = $value;
            }
        }

        return $value;
    }
} 
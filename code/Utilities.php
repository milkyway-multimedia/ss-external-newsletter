<?php
/**
 * Milkyway Multimedia
 * Utilities.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter;


class Utilities {
    public static $environment = [];

	public static function config() {
		return \Config::inst()->forClass('EmailCampaigns');
	}

	public static function settings() {
		return \Injector::inst()->get('Milkyway\SS\ExternalNewsletter\Contracts\Config');
	}

	public static function using() {
		return static::settings()->prefix();
	}

    public static function env_value($setting, \ViewableData $object = null) {
        if($object && $object->$setting)
            return $object->$setting;

        if(isset(self::$environment[$setting]))
            return self::$environment[$setting];

        $value = null;

	    $mapping = static::settings()->map();
	    $prefix = static::using();

        if(isset($mapping[$setting])) {
            $dbSetting = $prefix . '_' . $setting;
            $envSetting = strtolower($prefix) . '_' . $mapping[$setting];

            if($object && $object->config()->$envSetting)
                $value = $object->config()->$envSetting;

	        if(!$value)
		        $value = $object->config()->{$mapping[$setting]};

            if (!$value) {
                $value = static::config()->$envSetting;
            }

            if (!$value && \ClassInfo::exists('SiteConfig')) {
                if (\SiteConfig::current_site_config()->$dbSetting) {
                    $value = \SiteConfig::current_site_config()->$dbSetting;
                } elseif (\SiteConfig::config()->$envSetting) {
                    $value = \SiteConfig::config()->$envSetting;
                }
            }

            if (!$value) {
                if (getenv($envSetting)) {
                    $value = getenv($envSetting);
                } elseif (isset($_ENV[$envSetting])) {
                    $value = $_ENV[$envSetting];
                }
            }

            if ($value) {
                self::$environment[$setting] = $value;
            }
        }

        return $value;
    }
} 
<?php
/**
 * Milkyway Multimedia
 * Utilities.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter;


class Utilities implements \PermissionProvider {
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

            if($object)
                $value = $object->config()->$envSetting;

	        if(!$value && $object)
		        $value = $object->config()->{$mapping[$setting]};

            if (!$value && \ClassInfo::exists('SiteConfig')) {
                if (\SiteConfig::current_site_config()->$dbSetting) {
                    $value = \SiteConfig::current_site_config()->$dbSetting;
                } elseif (\SiteConfig::config()->$envSetting) {
                    $value = \SiteConfig::config()->$envSetting;
                }
            }

            if (!$value) {
                $value = static::config()->$envSetting;
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

    /**
     * Arrays cant be set in php environment, so we can split those by a delimiter (defaults to comma)
     *
     * @param $value
     *
     * @return array
     */
    public static function csv_to_array($value, $delimiter = ',')
    {
        if(is_array($value))
            return $value;
        elseif (strpos($value, $delimiter) !== false) {
            return explode($delimiter, $value);
        } else {
            return [$value];
        }
    }

    public function providePermissions() {
        return array(
            'MAILING_LISTS_CREATE' => array(
                'name' => _t('ExternalNewsletter.PERMISSION-MAILING_LISTS_CREATE', 'Can create new mailing lists'),
                'category' => _t('ExternalNewsletter.PERMISSION-CATEGORY', singleton('ExternalNewsletterAdmin')->Title),
            )
        );
    }
} 
<?php
/**
 * Milkyway Multimedia
 * MailchimpAdmin.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class MailchimpAdmin extends ModelAdmin {
    private static $menu_title = "Newsletter";
    private static $url_segment = "newsletters";
    private static $menu_priority = 1;

    public $showImportForm = false;

    private static $managed_models = array(
        'MailchimpCampaign',
    );

    public function alternateAccessCheck() {
        return !$this->config()->hidden;
    }
} 
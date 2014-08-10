<?php

/**
 * Milkyway Multimedia
 * ExternalNewsletterAdmin.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class ExternalNewsletterAdmin extends ModelAdmin
{
	private static $menu_title = "Email Campaigns";
	private static $url_segment = "email-campaigns";
	private static $menu_priority = 1;

	public $showImportForm = false;

	private static $managed_models = [
		'ExtCampaign',
		'ExtList',
	];

	public function alternateAccessCheck()
	{
		return !$this->config()->hidden;
	}
} 
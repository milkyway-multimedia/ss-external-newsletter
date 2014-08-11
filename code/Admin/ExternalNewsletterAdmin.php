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
		return !$this->config()->hidden && \Milkyway\SS\ExternalNewsletter\Utilities::env_value('APIKey', $this);
	}

	public function getEditForm($id = null, $fields = null) {
		$this->beforeExtending('updateEditForm', function(Form $form) {
			if($lists = $form->Fields()->fieldByName($this->sanitiseClassName('ExtList'))) {
				$lists->Config->addComponents(
					new GridFieldAjaxRefresh(5000)
				);
			}
		});

		return parent::getEditForm($id, $fields);
	}

	public function getList() {
		$this->beforeExtending('updateList', function($list) {

		});

		return parent::getList();
	}
} 
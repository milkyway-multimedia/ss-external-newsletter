<?php

/**
 * Milkyway Multimedia
 * ExternalNewsletterAdmin.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use \Milkyway\SS\ExternalNewsletter\Utilities;

class ExtNewsletterAdmin extends ModelAdmin
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
		return !$this->config()->hidden && Utilities::env_value('APIKey', $this);
	}

	public function getEditForm($id = null, $fields = null) {
		$this->beforeExtending('updateEditForm', function(Form $form) {
			if($lists = $form->Fields()->fieldByName($this->sanitiseClassName('ExtList'))) {
				singleton('ExtList')->sync();

				$lists->Config->addComponents(
					new GridFieldAjaxRefresh(10000)
				);
			}
		});

		return parent::getEditForm($id, $fields);
	}

	public function getList() {
		$this->beforeExtending('updateList', function($list) {
			if($listIds = Utilities::env_value('AllowedLists')) {
				if ((strpos($listIds, ',') !== false) || is_array($listIds))
					$listsIds = is_array($listIds) ? $listIds : explode(',', $listIds);
				else
					$listsIds = [$listIds];

				$list->filter('ExtId', $listsIds);
			}
		});

		return parent::getList();
	}
} 
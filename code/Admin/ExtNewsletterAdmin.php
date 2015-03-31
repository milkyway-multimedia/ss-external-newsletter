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
        'ExtSubscriber',
	];

	public function alternateAccessCheck()
	{
		return !$this->config()->hidden && singleton('env')->get('APIKey', null, ['objects' => [$this]]);
	}

	public function getEditForm($id = null, $fields = null) {
		$this->beforeExtending('updateEditForm', function(Form $form) {
			if($campaigns = $form->Fields()->fieldByName($this->sanitiseClassName('ExtCampaign'))) {
				if($addButton = $campaigns->Config->getComponentByType('GridFieldAddNewButton'))
					$addButton->setButtonName(_t('ExternalNewsletter.START_CAMPAIGN', 'Start campaign'));
			}

                if(!Utilities::env_value('NoListSync') && $lists = $form->Fields()->fieldByName($this->sanitiseClassName('ExtList'))) {
                    singleton('ExtList')->sync();
                }

                if($subscribers = $form->Fields()->fieldByName($this->sanitiseClassName('ExtSubscriber'))) {
                    singleton('ExtSubscriber')->sync();

                    $subscribers->Config->addComponents(
                        new GridFieldAjaxRefresh(20000)
                    );

                    if($detailForm = $subscribers->Config->getComponentByType('GridFieldDetailForm')) {
                        $detailForm->setItemEditFormCallback(function($form, $controller) {
                                if(!$controller->record->ID && \ExtList::get()->where('ExtId IS NOT NULL')->exists()) {
                                    $form->Fields()->insertBefore(\CheckboxSetField::create('ExtListId', _t('ExternalNewsletter.ExtListId', 'Select list(s) to subscribe the user to'), \ExtList::get()->where('ExtId IS NOT NULL')->map('ExtId', 'Title')->toArray(), \ExtList::get()->where('ExtId IS NOT NULL')->column('ExtId')), 'FirstName');
                                }
                            }
                        );
                    }
                }
		});

		return parent::getEditForm($id, $fields);
	}

	public function getList() {
		$this->beforeExtending('updateList', function($list) {
                if($this->modelClass == get_class(singleton('ExtList'))) {
                    if ($listIds = Utilities::env_value('AllowedLists')) {
                        $listsIds = Utilities::csv_to_array($listIds);

                        $list->filter('ExtId', $listsIds);
                    }
                }
		});

		return parent::getList();
	}
} 
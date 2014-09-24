<?php

/**
 * Milkyway Multimedia
 * ExtCampaign.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class ExtCampaign extends DataObject
{
	private static $singular_name = 'Regular Campaign';

	private static $description = 'Send a HTML Newsletter';

	private static $db = [
		'Subject'   => 'Varchar',
		'FromEmail' => 'Varchar',
		'FromName'  => 'Varchar',

		'Content'   => 'HTMLText',

		'Template'  => 'Varchar',
	];

	private static $has_many = [
		'Sent'      => 'ExtSendLog',
		'Scheduled' => 'ExtSchedule',
	];

	private static $summary_fields = [
		'Subject',
	];

	protected $templateProvider = 'Milkyway\SS\ExternalNewsletter\Contracts\Template';

	public function getTitle()
	{
		return $this->Subject;
	}

	public function getCMSFields()
	{
		$this->beforeExtending('updateCMSFields', function ($fields) {
				$fields->removeByName('Template');

				if (!$this->exists())
					$fields->insertBefore(FormMessageField::create('NOTE-UNSAVED', 'You can start sending this campaign and testing it once it has been saved', 'info')->cms(), 'Subject');

				if (($sent = $fields->dataFieldByName('Sent')) && ($sent instanceof \GridField)) {
					$sent->Config->removeComponentsByType('GridFieldAddExistingAutocompleter');

					if ($addButton = $sent->Config->getComponentByType('GridFieldAddNewButton'))
						$addButton->setButtonName(_t('ExternalNewsletter.SEND_THIS_CAMPAIGN', 'Send this campaign to a list'));
				}

				if(($templates = $this->availableTemplates()) && count($templates)) {
					$fields->insertBefore(\DropdownField::create('Template', _t('ExternalNewsletter.TEMPLATE', 'Template'), $templates), 'Content');
				}
			}
		);

		return parent::getCMSFields();
	}

	protected function availableTemplates() {
		$params = [];
		$this->extend('updateParamsForTemplateRetrieval', $params);
		return \Injector::inst()->createWithArgs($this->templateProvider, [\Milkyway\SS\ExternalNewsletter\Utilities::env_value('APIKey', $this->owner), 6])->get($params);
	}
}
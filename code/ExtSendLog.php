<?php

/**
 * Milkyway Multimedia
 * ExtSendLog.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

use \Milkyway\SS\ExternalNewsletter\Utilities;

class ExtSendLog extends DataObject
{
	private static $singular_name = 'Send a campaign';

	private static $description = 'This is a log for sending a campaign. You can send the same campaign to many different lists etc.';

	private static $db = [
		'Status'     => "Enum('save,test,paused,scheduled,sending,sent')",
		'ExtId'       => 'Varchar',
		'ExtSlug'    => 'Varchar',

		'Sent'       => 'Datetime',
		'NumberSent' => 'Int',

		'Extras'     => 'Text',
	];

	private static $has_one = [
		'Campaign' => 'ExtCampaign',
		'Author'   => 'Member',
		'Sender'   => 'Member',
		'List'     => 'ExtList',
	];

	private static $summary_fields = [
		'Title',
		'Sent',
		'NumberSent',
	];

	public function getTitle()
	{
		return 'Send to ' . $this->List()->Title;
	}

	protected $handler = 'Milkyway\SS\ExternalNewsletter\Contracts\Campaign';
	protected $manager = 'Milkyway\SS\ExternalNewsletter\Contracts\CampaignManager';

	public function getCMSFields()
	{
		// Download external lists before we show the send log form
		singleton('ExtList')->sync();

		$this->beforeExtending('updateCMSFields', function ($fields) {
				if(!ExtCampaign::get()->exists()) {
					$fields->insertBefore(\FormMessageField::create('NO-CAMPAIGNS', _t('ExternalNewsletter.ERROR-NO_CAMPAIGNS', 'You have not added an email campaign to send yet. <a href="{cmsLink}">Create one now</a>.', ['cmsLink' => singleton('ExtNewsletterAdmin')->Link()]), 'warning')->cms(), 'Status');
					$fields->removeByName('CampaignID');
				}
				elseif(!ExtList::get()->exists()) {
					$fields->insertBefore(\FormMessageField::create('NO-LISTS', _t('ExternalNewsletter.ERROR-NO_LISTS', 'There are no lists to send this email campaign to yet. <a href="{cmsLink}">Create one now</a>.', ['cmsLink' => singleton('ExtNewsletterAdmin')->Link()]), 'warning')->cms(), 'Status');
				}

				if (!$this->ExtId)
					$fields->removeByName('ExtId');

				if (!$this->ExtSlug)
					$fields->removeByName('ExtSlug');

				//if (!$this->Status)
					$fields->removeByName('Status');

				if (!$this->NumberSent)
					$fields->removeByName('NumberSent');

				if (!$this->Sent)
					$fields->removeByName('Sent');

				$fields->removeByName('AuthorID');

				if($this->ListID && $lists = $fields->dataFieldByName('ListID'))
					$fields->replaceField('ListID', $lists->performReadonlyTransformation()->setName('ListID'));
			}
		);

		return parent::getCMSFields();
	}

	protected function validate() {
		$this->beforeExtending('validate', function(\ValidationResult $result) {
			if(!$this->ListID)
				$result->error(_t('ExternalNewsletter.ERROR-NO_LIST_TO_SEND_TO', 'Please select a list to send to'));
		});

		return parent::validate();
	}

	public function canView($member = null)
	{
		$this->beforeExtending(__FUNCTION__, function ($member = null) {
				if(!\Permission::check('NEWSLETTER_MANAGE') && !\Permission::check('NEWSLETTER_VIEW'))
					return false;
			}
		);

		return parent::canView($member);
	}

	public function canCreate($member = null)
	{
		$this->beforeExtending(__FUNCTION__, function ($member = null) {
				if(!\Permission::check('NEWSLETTER_MANAGE'))
					return false;
			}
		);

		return parent::canCreate($member);
	}

	public function canEdit($member = null)
	{
		$this->beforeExtending(__FUNCTION__, function ($member = null) {
				if($this->Status != 'save' || !\Permission::check('NEWSLETTER_MANAGE'))
					return false;
			}
		);

		return parent::canEdit($member);
	}

	public function canSend($member = null)
	{
		$this->beforeExtending(__FUNCTION__, function ($member = null) {
				if($this->Status != 'save' || !\Permission::check('NEWSLETTER_SEND'))
					return false;
			}
		);

		return $this->canEdit($member);
	}

	public function canDelete($member = null)
	{
		$this->beforeExtending(__FUNCTION__, function ($member = null) {
				if($this->Status != 'save' || !\Permission::check('NEWSLETTER_MANAGE'))
					return false;
			}
		);

		return parent::canDelete($member);
	}

	public function onBeforeWrite()
	{
		if (!$this->AuthorID)
			$this->AuthorID = Member::currentUserID();

		parent::onBeforeWrite();
		$this->syncUpdate();
	}

	public function onBeforeDelete()
	{
		parent::onBeforeDelete();
		$this->syncDelete();
	}

	protected function syncCreate()
	{
		if (!$this->ExtId) {
			\Injector::inst()->get($this->manager)->create(
				\Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this)]),
				$this
			);

			$this->write();
		}
	}

	protected function syncDelete()
	{
		if ($this->ExtId) {
			\Injector::inst()->get($this->manager)->delete(
				\Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this)]),
				$this
			);
		}
	}

	protected function syncUpdate()
	{
		if ($this->ExtId) {
			\Injector::inst()->get($this->manager)->update(
				\Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this)]),
				$this
			);

			$this->write();
		}
	}

	public function getDetails()
	{
		return unserialize($this->Extras);
	}

	public function setDetails($details)
	{
		$this->Extras = serialize($details);
	}
}
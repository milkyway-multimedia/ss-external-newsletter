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
		'Status'     => "Enum('save,paused,scheduled,sending,sent')",
		'ExtId'       => 'Varchar',
		'ExtSlug'    => 'Varchar',

		'Sent'       => 'Datetime',
		'NumberSent' => 'Int',
	];

	private static $has_one = [
		'Campaign' => 'ExtCampaign',
		'Author'   => 'Member',
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

	private $updated = false;
	private $attemptFix = false;

	public function getCMSFields()
	{
		// Download external lists before we show the send log form
		singleton('ExtList')->sync();

		$this->beforeExtending('updateCMSFields', function ($fields) {
				if(!ExtCampaign::get()->exists()) {
					$fields->insertBefore(\FormMessageField::create('NO-CAMPAIGNS', _t('ExternalNewsletter.ERROR-NO_CAMPAIGNS', 'You have not added an email campaign to send yet. <a href="{cmsLink}">Create one now</a>.', ['cmsLink' => singleton('ExtNewsletterAdmin')->Link()]), 'warning')->cms(), 'Status');
					$fields->removeByName('CampaignID');
				}

				if (!$this->ExtId)
					$fields->removeByName('ExtId');

				if (!$this->ExtSlug)
					$fields->removeByName('ExtSlug');

				if (!$this->Status)
					$fields->removeByName('Status');

				if (!$this->NumberSent)
					$fields->removeByName('NumberSent');

				if (!$this->Sent)
					$fields->removeByName('Sent');

				$fields->removeByName('AuthorID');
				$fields->removeByName('ListID');
			}
		);

		return parent::getCMSFields();
	}

	public function canEdit($member = null)
	{
		$this->beforeExtending(__METHOD__, function ($member = null) {
				return $this->Status == 'save';
			}
		);

		return parent::canEdit($member);
	}

	public function canSend($member = null)
	{
		$this->beforeExtending(__METHOD__, function ($member = null) {
				return $this->Status == 'save';
			}
		);

		return parent::canEdit($member);
	}

	public function canDelete($member = null)
	{
		$this->beforeExtending(__METHOD__, function ($member = null) {
				return $this->Status == 'save';
			}
		);

		return parent::canEdit($member);
	}

	public function onBeforeWrite()
	{
		if (!$this->AuthorID)
			$this->AuthorID = Member::currentUserID();

		parent::onBeforeWrite();
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

	public function saveMailingListID($listId = '')
	{
		if (strpos($listId, '|') !== false) {
			list($listId, $listName) = explode('|', $listId);
		} else {
			$listName = '';
		}

		$this->ListID = \ExtList::find_or_make(['ExtId' => $listId], ['Title' => $listName])->ID;
	}

	public function getMailingListID()
	{
		return $this->List()->ExtId . '|' . $this->List()->Title;
	}
}
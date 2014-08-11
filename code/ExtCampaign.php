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
		'Subject'  => 'Varchar',
		'From'     => 'Varchar',
		'FromName' => 'Varchar',

		'Content'  => 'HTMLText',

		'Template' => 'Varchar',
	];

	private static $has_many = [
		'Sent'      => 'ExtSendLog',
		'Scheduled' => 'ExtSchedule',
	];

	private static $summary_fields = [
		'Title',
	];

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
			}
		);

		return parent::getCMSFields();
	}
}
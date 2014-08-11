<?php
/**
 * Milkyway Multimedia
 * ExtList.php
 *
 * @package reggardocolaianni.com
 * @author  Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class ExtList extends DataObject
{
	private static $singular_name = 'Mailing List';

	private static $db = [
		'Title' => 'Varchar',
		'ExtId' => 'Varchar',
	];

	private static $indexes = [
		'ExtId' => true,
	];

	private static $has_many = [
		'Received' => 'ExtSendLog',
	];

	protected $provider = 'Milkyway\SS\ExternalNewsletter\Contracts\Lists';

	public static function find_or_make($filter = [], $data = [])
	{
		if (!($list = static::get()->filter($filter)->first())) {
			$list = static::create(array_merge($filter, $data));
			$list->write();
			$list->isNew = true;
		}

		return $list;
	}

	public function getCMSFields()
	{
		$this->beforeExtending(
			'updateCMSFields',
			function ($fields) {
				if ($this->ExtId) {
					$fields->addFieldsToTab(
						'Root.AllEmails',
						[
							FormMessageField::create(
								'NOTE-AllEmails',
								'This is a list of all emails subscribed to this mailing list from all sources',
								'info'
							)->cms(),
							GridField::create(
								'UpdatedEmails',
								'Emails',
								$this->UpdatedEmails(false),
								GridFieldConfig_RecordEditor::create(50)
									->removeComponentsByType('GridFieldFilterHeader')
									->removeComponentsByType('GridFieldDetailForm')
									->removeComponentsByType('GridFieldDeleteAction')
									->addComponents(new ExternalDataGridFieldDetailForm())
									->addComponents(new ExternalDataGridFieldDeleteAction())
									->addComponents(new GridFieldAjaxRefresh(5000))
							)
						]
					);
				}
			}
		);

		return parent::getCMSFields();
	}

	public function AllEmails()
	{
		return singleton('Milkyway\SS\ExternalNewsletter\External\Subscriber')->fromExternalList($this->ExtId);
	}

	public function UpdatedEmails($cache = true)
	{
		return singleton('Milkyway\SS\ExternalNewsletter\External\Subscriber')->fromExternalList($this->ExtId, $cache);
	}

	public function requireDefaultRecords()
	{
		parent::requireDefaultRecords();

		// Sync with External Newsletter Database
		$lists = \Injector::inst()->createWithArgs($this->provider, [\Milkyway\SS\ExternalNewsletter\Utilities::env_value('APIKey', $this)])->get();

		foreach ($lists as $list) {
			if (isset($list['id'])) {
				$list['Title'] = (isset($list['name']) ? $list['name'] : '');

				if (static::find_or_make(['ExtId' => $list['id']], $list)->isNew)
					\DB::alteration_message((isset($list['name']) ? $list['name'] : $list['id']) . ' List grabbed from ' . \Milkyway\SS\ExternalNewsletter\Utilities::using(), 'created');
			}
		}
	}
} 
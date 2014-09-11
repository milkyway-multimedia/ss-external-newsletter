<?php namespace Milkyway\SS\ExternalNewsletter\Extensions;

use Milkyway\SS\ExternalNewsletter\Utilities;

class Subscriber extends \DataExtension {
    private static $db = [
        'EUId' => 'Varchar',
    ];

    private static $many_many = [
        'Lists' => 'ExtList',
    ];

    private static $many_many_extraFields = [
        'Lists' => [
            'Subscribed' => 'Datetime',
            'LEId' => 'Varchar',
        ],
    ];

    protected $relation;
    protected $emailField = 'Email';

    protected $handler = 'Milkyway\SS\ExternalNewsletter\Contracts\Subscriber';
    protected $manager = 'Milkyway\SS\ExternalNewsletter\Contracts\SubscriberManager';

    protected $listsHandler = 'Milkyway\SS\ExternalNewsletter\Contracts\Lists';

    public function __construct($relation = '', $emailField = 'Email')
    {
        parent::__construct();
        $this->emailField = $emailField;
        $this->relation = $relation;
    }

    public static function get_extra_config($class, $extension, $args) {
        $relation = isset($args[0]) ? $args[0] : $class;

        \Config::inst()->update('ExtList', 'belongs_many_many', [
                $relation => $class,
            ]
        );

        return null;
    }

    public function updateCMSFields(\FieldList $fields) {
        if($this->owner->EUId) {
            if(!\Permission::check('ADMIN')) {
                $fields->replaceField(
                    'EUId',
                    \ReadonlyField::create('EUId', _t('ExternalNewsletter.EUId', 'Unique Email ID'))->setDescription(
                        _t(
                            'ExternalNewsletter.DESC-EUId',
                            'This is the ID of this subscriber that it is tracking on your mailing list provider.'
                        )
                    )
                );
            }
	        elseif($euid = $fields->dataFieldByName('EUId')) {
		        $euid->setDescription(
			        _t(
				        'ExternalNewsletter.DESC-EUId',
				        'This is the ID of this subscriber that it is tracking on your mailing list provider.'
			        )
		        );
	        }
        }
        else
            $fields->removeByName('EUId');

	    $fields->removeByName('Lists');

	    if($this->owner->ID && \ExtList::get()->where('ExtId IS NOT NULL')->exists()) {
		    $fields->insertBefore(\CheckboxSetField::create('Lists', _t('ExternalNewsletter.Lists', 'List(s)'), \ExtList::get()->where('ExtId IS NOT NULL')->toArray(), $this->owner->Lists()->column('ID')), 'FirstName');
	    }
    }

    public function onAfterManyManyRelationAdd($list, &$extraFields) {
        if($list && $list->dataClass() == get_class(singleton('ExtList'))) {
            if($this->owner->ExtListId)
                $listId = $this->owner->ExtListId;
            else
                $listId = \DataList::create($list->dataClass())->byId($list->getForeignID())->ExtId;

            if($listId)
                $this->owner->subscribeToExternalList(['list' => $listId]);
        }
    }

    public function onAfterManyManyRelationRemove($list) {
        if($list && $list->dataClass() == get_class(singleton('ExtList'))) {
            if($this->owner->ExtListId)
                $listId = $this->owner->ExtListId;
            else {
                $listId = \DataList::create($list->dataClass())->byId($list->getForeignID())->ExtId;
            }

            if($listId)
                $this->owner->unsubscribeFromExternalList(['list' => $listId]);
        }
    }

	public function onBeforeWrite() {
		if($this->owner->ExtListId)
			$this->owner->subscribeToExternalList(['list' => $this->owner->ExtListId]);
	}

	public function onAfterDelete() {
		if($this->owner->ExtListId)
			$this->owner->unsubscribeFromExternalList(['list' => $this->owner->ExtListId]);
	}

	public function validate(\ValidationResult $result) {
		if($email = $this->owner->{$this->emailField}) {
			if(!\Email::is_valid_address($email))
				$result->error(_t('ExternalNewsletter.INVALID_EMAIL', '{email} is not a valid email address', ['name' => $this->emailField, 'email' => $email]));

			$check = $this->owner->get()->filter($this->emailField, $email);

			if ($this->owner->ID)
				$check = $check->exclude('ID', $this->owner->ID);

			if ($check->exists()) {
				$result->error(_t('ExternalNewsletter.EMAIL_EXISTS', '{name}: {email} already exists in database', ['name' => $this->emailField, 'email' => $email]));
			}
		}
	}

	public function findOrMake($filter = [], $data = []) {
		if (!($item = $this->owner->get()->filter($filter)->first())) {
			$item = $this->owner->create(array_merge($filter, $data));
			$item->write();
			$item->isNew = true;
		}

		return $item;
	}

    public function fromExternalList($listId, $cache = true) {
        $list = \ArrayList::create();
        $class = get_class($this->owner);

        $result = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner), $cache ? 2 : 0])->get(['id' =>$listId]);
		$manager = \Injector::inst()->get($this->manager);

        foreach($result as $item) {
            $record = \Object::create($class, $item);

	        $manager->applyExternalVars($record, $item);

            $record->ExtListId = $listId;

            $list->push($record);
        }

        return $list;
    }

    public function sync($useListId = '', $deleteNonExisting = false) {
	    $results = [];

        // Sync with External Subscriber Database
        if($useListId)
            $results[$useListId] = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner), 0])->get(['id' =>$useListId]);
        else {
            $lists = \Injector::inst()->createWithArgs($this->listsHandler, [Utilities::env_value('APIKey', $this->owner)])->get();
            $allowed = Utilities::csv_to_array(Utilities::env_value('AllowedLists'));

            foreach($lists as $list) {
                if (isset($list['id']) && in_array($list['id'], $allowed)) {
                    $results[$list['id']] = \Injector::inst()->createWithArgs(
                        $this->handler,
                        [Utilities::env_value('APIKey', $this->owner), 0]
                    )->get(['id' => $list['id']]);
                }
            }
        }

        $existing = [];

        foreach ($results as $listId => $items) {
            if(count($items)) {
                $subscribed = [];
                $list = \ExtList::get()->filter(['ExtId' => $listId])->first();

                foreach($items as $item) {
                    $existing[] = $subscribed[] = $item['id'];

                    $record = $this->owner->findOrMake(['EUId' => $item['id'], $this->emailField => $item['email']], $item);
                    \Injector::inst()->get($this->manager)->applyExternalVars($record, $item);
                    $record->write();

                    if($list)
                        $record->Lists()->add($list, $record->ExtraDataOnSubscription($item));
                }

                if($list) {
                    $list->getManyManyComponents($this->relation())->exclude('EUId', $subscribed)->removeAll();
                }
            }
        }

        if($deleteNonExisting)
            $this->owner->get()->exclude('EUId', $existing)->removeAll();
    }

    public function subscribeToExternalList($params = [], $addLocally = false) {
        if($this->owner->{$this->emailField} || isset($params['email'])) {
	        if(!isset($params['email']))
		        $params['email'] = $this->owner->{$this->emailField};

	        if(!isset($params['list']))
		        $params['list'] = $this->owner->ExtListIds;

	        $manager = \Injector::inst()->get($this->manager);
	        $handler = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner), 0]);
	        $lists = Utilities::csv_to_array($params['list']);

	        foreach ($lists as $list) {
		        $params['list'] = $list;
		        $manager->subscribe($handler, $this->owner, $params);
	        }

            if($addLocally) {
                $leid = isset($email['leid']) ? $email['leid'] : '';
                $this->owner->addToExternalLists($leid, $lists);
            }
        }
    }

    public function unsubscribeFromExternalList($params = [], $removeLocally = false) {
	    if($this->owner->{$this->emailField} || isset($params['email'])) {
		    if(!isset($params['email']))
			    $params['email'] = $this->owner->{$this->emailField};

            if(!isset($params['list']))
	            $params['list'] = $this->owner->ExtListIds;

		    $manager = \Injector::inst()->get($this->manager);
		    $handler = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner), 0]);
			$lists = Utilities::csv_to_array($params['list']);

		    foreach ($lists as $list) {
			    $params['list'] = $list;
			    $manager->unsubscribe($handler, $this->owner, $params);
		    }

            if($removeLocally)
                $this->owner->removeFromExternalLists($lists);
	    }
    }

	public function addToExternalLists($leid = '', $lists = null) {
		if(!$lists)
			$lists = $this->owner->ExtListIds;

		$lists = Utilities::csv_to_array($lists);

		foreach($lists as $listId) {
			$list = singleton($this->owner->Lists()->dataClass())->findOrMake(['ExtId' => $listId]);

			if($this->owner instanceof \DataObject)
				$this->owner->Lists()->add($list, $this->owner->ExtraDataOnSubscription(['leid' => $leid]));
		}
	}

	public function removeFromExternalLists($lists) {
		if(!$lists)
			$lists = $this->owner->ExtListIds;

		$lists = Utilities::csv_to_array($lists);

		foreach($lists as $listId) {
			$list = singleton($this->owner->Lists()->dataClass())->findOrMake(['ExtId' => $listId]);

			if($this->owner instanceof \DataObject)
				$this->owner->Lists()->remove($list);
		}
	}

	public function getDefaultExternalListID() {
		return Utilities::env_value('DefaultLists', $this->owner);
	}

    public function ListParams($type = 'mailchimp') {
        $params = [];

        $vars = $this->getMergeVars();

        if(count($vars))
            $params['merge_vars'] = $vars;

        return array_merge((array)Utilities::env_value('DefaultParams', $this->owner), $params);
    }

    public function InterestGroups($type = 'mailchimp') {
        return Utilities::env_value('DefaultGroups', $this->owner);
    }

    public function ExtraDataOnSubscription($response = []) {
        $extraData = [
            'Subscribed' => \SS_Datetime::now()->Rfc2822(),
            'LEId' => isset($response['leid']) ? $response['leid'] : '',
        ];

        $this->owner->extend('updateExtraDataOnSubscription', $extraData, $response);

        return $extraData;
    }

    protected function getMergeVars() {
        $vars = [];

        if($this->owner->MailchimpMergeVars) {
            foreach($this->owner->MailchimpMergeVars as $db => $mailchimp) {
                $vars[$mailchimp] = $this->owner->$db;
            }
        }

        return $vars;
    }

	protected function getExtListIds() {
		$list = $this->owner->ExtListId;

		if(!$list) {
			$list = $this->owner->Lists()->where('ExtId IS NOT NULL')->column('ExtId');

			if(!count($list))
				$list = Utilities::csv_to_array($this->owner->DefaultExternalListId);
		}

		return $list;
	}

    protected function relation() {
        if(!$this->relation)
            $this->relation = get_class($this->owner);

        return $this->relation;
    }
}
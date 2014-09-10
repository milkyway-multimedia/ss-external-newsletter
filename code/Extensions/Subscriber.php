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

    public function findOrMake($filter = [], $data = [])
    {
        if (!($item = $this->owner->get()->filter($filter)->first())) {
            $item = $this->owner->create(array_merge($filter, $data));
            $item->write();
            $item->isNew = true;
        }

        return $item;
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
        }
        else
            $fields->removeByName('EUId');
    }

    public function onAfterManyManyRelationAdd($list, &$extraFields) {
        if($list && $list->dataClass() == get_class(singleton('ExtList'))) {
            if($this->owner->ExtListId)
                $listId = $this->owner->ExtListId;
            else {
                $listId = \DataList::create($list->dataClass())->byId($list->getForeignID())->ExtId;
            }

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

    public function fromExternalList($listId, $cache = true) {
        $list = \ArrayList::create();
        $class = get_class($this->owner);

        $result = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner), $cache ? 2 : 0])->get(['id' =>$listId]);

        foreach($result as $item) {
            $record = \Object::create($class, $item);

	        \Injector::inst()->get($this->manager)->applyExternalVars($record, $item);

            $record->ExtListId = $listId;

            $list->push($record);
        }

        return $list;
    }

    public function sync($useListId = '', $deleteNonExisting = false) {
        // Sync with External Subscriber Database
        if($useListId)
            $results[$useListId] = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner)])->get(['id' =>$useListId]);
        else {
            $lists = \Injector::inst()->createWithArgs($this->listsHandler, [Utilities::env_value('APIKey', $this->owner)])->get();
            $allowed = Utilities::csv_to_array(Utilities::env_value('AllowedLists'));

            foreach($lists as $list) {
                if (isset($list['id']) && in_array($list['id'], $allowed)) {
                    $results[$list['id']] = \Injector::inst()->createWithArgs(
                        $this->handler,
                        [Utilities::env_value('APIKey', $this->owner)]
                    )->get(['id' => $list['id']]);
                }
            }
        }

        $existing = array();

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
                $params['list'] = $this->owner->ExtListId ? $this->owner->ExtListId : $this->owner->ExternalListID;

            $email = \Injector::inst()->get($this->manager)->subscribe(
	            \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner)]),
	            $this->owner,
	            $params
            );

            if($addLocally) {
                $leid = isset($email['leid']) ? $email['leid'] : '';
                $this->owner->addToExternalLists($leid);
            }
        }
    }

    public function unsubscribeFromExternalList($params = [], $removeLocally = false) {
	    if($this->owner->{$this->emailField} || isset($params['email'])) {
		    if(!isset($params['email']))
			    $params['email'] = $this->owner->{$this->emailField};

            if(!isset($params['list']))
                $params['list'] = $this->owner->ExtListId ? $this->owner->ExtListId : $this->owner->ExternalListID;

		   \Injector::inst()->get($this->manager)->unsubscribe(
			    \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner)]),
			    $this->owner,
			    $params
		    );

            if($removeLocally) {
                $listId = isset($params['id']) ? $params['id'] : '';
                $this->owner->removeFromExternalLists($listId);
            }
	    }
    }

    public function getExternalListID() {
        return Utilities::env_value('DefaultLists', $this->owner);
    }

    public function getMailchimpListParams() {
        $params = [];

        $vars = $this->getMergeVars();

        if(count($vars))
            $params['merge_vars'] = $vars;

        return array_merge((array)Utilities::env_value('DefaultParams', $this->owner), $params);
    }

    public function getMailchimpInterestGroups() {
        return Utilities::env_value('Mailchimp_DefaultGroups', $this->owner);
    }

    public function addToExternalLists($leid = '') {
        if($listId = $this->owner->ExternalListID) {
            $listsIds = Utilities::csv_to_array($listId);

            foreach($listsIds as $listId) {
                $list = \ExtList::find_or_make(['ExtId' => $listId]);

                if($this->owner instanceof \DataObject)
                    $this->owner->Lists()->add($list, ['Subscribed' => \SS_Datetime::now()->Rfc2822(), 'LEId' => $leid]);
            }
        }
    }

    public function removeFromExternalLists() {
        if($listId = $this->owner->ExternalListID) {
            $listsIds = Utilities::csv_to_array($listId);

            foreach($listsIds as $listId) {
                $list = \ExtList::find_or_make(['ExtId' => $listId]);

                if($this->owner instanceof \DataObject)
                    $this->owner->Lists()->remove($list);
            }
        }
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

    protected function relation() {
        if(!$this->relation)
            $this->relation = get_class($this->owner);

        return $this->relation;
    }
}
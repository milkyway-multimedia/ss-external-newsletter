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

    protected $emailField = 'Email';

    protected $handler = 'Milkyway\SS\ExternalNewsletter\Contracts\Subscriber';
    protected $manager = 'Milkyway\SS\ExternalNewsletter\Contracts\SubscriberManager';

    public function __construct($type = '', $emailField = 'Email')
    {
        parent::__construct();
        $this->emailField = $emailField;
    }

    public static function get_extra_config($class, $extension, $args) {
        $type = isset($args[0]) ? $args[0] : $class;

        \Config::inst()->update('ExtList', 'belongs_many_many', [
                $type => $class,
            ]
        );

        return null;
    }

    public function updateCMSFields(\FieldList $fields) {
        $fields->removeByName('EUId');
    }

    public function onBeforeWrite() {
        if(!Utilities::env_value('Subscribe_OnWrite', $this->owner))
            return;

        $this->owner->subscribe();
    }

    public function onBeforeDelete() {
        if(!Utilities::env_value('Unsubscribe_OnDelete', $this->owner))
            return;

        $this->owner->unsubscribe();
    }

    public function fromExternalList($listId, $cache = true) {
        $list = \ExternalDataList::create();
        $class = get_class($this->owner);

        $result = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner), $cache ? 2 : 0])->get($listId);

        foreach($result as $item) {
            $record = \Object::create($class, $item);

	        \Injector::inst()->get($this->manager)->applyExternalVars($record, $item);

            $record->ExtListId = $listId;

            $list->push($record);
        }

        return $list;
    }

    public function subscribeToExternalList($params = []) {
        if($this->owner->{$this->emailField} || isset($params['email'])) {
	        if(!isset($params['email']))
		        $params['email'] = $this->owner->{$this->emailField};

            $email = \Injector::inst()->get($this->manager)->subscribe(
	            \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner)]),
	            $this->owner,
	            $params
            );

            $leid = isset($email['leid']) ? $email['leid'] : '';
            $this->owner->addToExternalLists($leid);
        }
    }

    public function unsubscribeFromExternalList($params = []) {
	    if($this->owner->{$this->emailField} || isset($params['email'])) {
		    if(!isset($params['email']))
			    $params['email'] = $this->owner->{$this->emailField};

		   \Injector::inst()->get($this->manager)->unsubscribe(
			    \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('APIKey', $this->owner)]),
			    $this->owner,
			    $params
		    );

		    $listId = isset($params['list_id']) ? $params['list_id'] : '';
		    $this->owner->removeFromExternalLists($listId);
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
            $listsIds = $this->convertListIdsToMany($listId);

            foreach($listsIds as $listId) {
                $list = \ExtList::find_or_make(['McId' => $listId]);

                if($this->owner instanceof \DataObject)
                    $this->owner->Lists()->add($list, ['Subscribed' => \SS_Datetime::now()->Rfc2822(), 'LEId' => $leid]);
            }
        }
    }

    public function removeFromExternalLists() {
        if($listId = $this->owner->ExternalListID) {
            $listsIds = $this->convertListIdsToMany($listId);

            foreach($listsIds as $listId) {
                $list = \ExtList::find_or_make(['McId' => $listId]);

                if($this->owner instanceof \DataObject)
                    $this->owner->Lists()->remove($list);
            }
        }
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

    /**
     * @param $listId
     *
     * @return array
     */
    protected function convertListIdsToMany($listId)
    {
        if ((strpos($listId, ',') !== false) || is_array($listId)) {
            $listsIds = is_array($listId) ? $listId : explode(',', $listId);
            return $listsIds;
        } else
            $listsIds = [$listId];
        {
            return $listsIds;
        }
    }
}
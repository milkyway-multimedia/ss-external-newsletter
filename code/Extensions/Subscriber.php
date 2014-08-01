<?php namespace Milkyway\SS\MailchimpSync\Extensions;

use Milkyway\SS\MailchimpSync\Utilities;

class Subscriber extends \DataExtension {
    private static $db = [
        'EUId' => 'Varchar',
    ];

    private static $many_many = [
        'Lists' => 'McList',
    ];

    private static $many_many_extraFields = [
        'Lists' => [
            'Subscribed' => 'Datetime',
            'LEId' => 'Varchar',
        ],
    ];

    protected $emailField = 'Email';

    protected $handler = 'Milkyway\SS\MailchimpSync\Handlers\Subscriber';

    public function __construct($type = '', $emailField = 'Email')
    {
        parent::__construct();
        $this->emailField = $emailField;
    }

    public static function get_extra_config($class, $extension, $args) {
        $type = isset($args[0]) ? $args[0] : $class;

        \Config::inst()->update('McList', 'belongs_many_many', [
                $type => $class,
            ]
        );

        return null;
    }

    public function updateCMSFields(FieldList $fields) {
        $fields->removeByName('EUId');
    }

    public function onBeforeWrite() {
        if(!Utilities::env_value('Mailchimp_Subscribe_OnWrite', $this->owner))
            return;

        $this->owner->subscribe();
    }

    public function onBeforeDelete() {
        if(!Utilities::env_value('Mailchimp_Unsubscribe_OnDelete', $this->owner))
            return;

        $this->owner->unsubscribe();
    }

    public function fromMailchimpList($listId) {
        $list = \ExternalDataList::create();
        $list->dataClass = get_class($this->owner);

        $result = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this->owner), 1])->get($listId);

        foreach($result as $item) {
            $record = \Object::create($list->dataClass, $item);

            if(isset($item['merges']) && $record->MailchimpMergeVars) {
                foreach($record->MailchimpMergeVars as $db => $var) {
                    if(isset($item['merges'][$var]))
                        $record->$db = $item['merges'][$var];
                }
            }

            $record->MailchimpListID = $listId;
            $record->Email = $record->email;
            $record->ID = $record->euid;

            $list->push($record);
        }

        return $list;
    }

    public function subscribeToMailchimp($params = []) {
        if($this->owner->{$this->emailField} && ($this->owner->SubcriberListID || $this->owner->MailchimpListID)) {
            $email = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this->owner)])->subscribe(
                array_merge([
                    'email' => $this->owner->{$this->emailField},
                    'list' => $this->owner->SubcriberListID ? $this->owner->SubcriberListID : $this->owner->MailchimpListID,
                    'groups' => $this->owner->MailchimpInterestGroups,
                    'double_optin' => Utilities::env_value('Mailchimp_DoubleOptIn', $this->owner),
                ], $params), $this->owner->MailchimpListParams
            );

            if(isset($email['euid']))
                $this->owner->EUId = $email['euid'];

            $leid = isset($email['leid']) ? $email['leid'] : '';
            $this->owner->addToMailchimpLists($leid);
        }
    }

    public function unsubscribeFromMailchimp($params = []) {
        if($this->owner->{$this->emailField} && ($this->owner->SubcriberListID || $this->owner->MailchimpListID)) {
            \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this->owner)])->unsubscribe(
                array_merge([
                    'email' => $this->owner->{$this->emailField},
                    'list' => $this->owner->SubcriberListID ? $this->owner->SubcriberListID : $this->owner->MailchimpListID,
                ], $params)
            );

            $this->owner->removeFromMailchimpLists();
        }
    }

    public function getMailchimpListID() {
        return Utilities::env_value('Mailchimp_DefaultLists', $this->owner);
    }

    public function getMailchimpListParams() {
        $params = [];

        $vars = $this->getMergeVars();

        if(count($vars))
            $params['merge_vars'] = $vars;

        return array_merge((array)Utilities::env_value('Mailchimp_DefaultParams', $this->owner), $params);
    }

    public function getMailchimpInterestGroups() {
        return Utilities::env_value('Mailchimp_DefaultGroups', $this->owner);
    }

    public function addToMailchimpLists($leid = '') {
        if($listId = $this->owner->MailchimpListID) {
            $listsIds = $this->convertListIdsToMany($listId);

            foreach($listsIds as $listId) {
                $list = \McList::find_or_make(['McId' => $listId]);

                if($this->owner instanceof \DataObject)
                    $this->owner->Lists()->add($list, ['Subscribed' => \SS_Datetime::now()->Rfc2822(), 'LEId' => $leid]);
            }
        }
    }

    public function removeFromMailchimpLists() {
        if($listId = $this->owner->MailchimpListID) {
            $listsIds = $this->convertListIdsToMany($listId);

            foreach($listsIds as $listId) {
                $list = \McList::find_or_make(['McId' => $listId]);

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
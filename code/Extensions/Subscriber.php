<?php namespace Milkyway\SS\MailchimpSync\Extensions;

use Milkyway\SS\MailchimpSync\Utilities;

class Subscriber extends \DataExtension {
    private static $db = [
        'EUID' => 'Varchar',
    ];

    private static $many_many = [
        'Lists' => 'McList',
    ];

    private static $many_many_extraFields = [
        'Lists' => [
            'Subscribed' => 'Datetime',
            'LEID' => 'Varchar',
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
        $fields->removeByName('EUID');
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

    public function fromList($listId) {
        $list = \ExternalDataList::create();
        $list->dataClass = get_class($this->owner);

        $result = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', singleton('Milkyway\SS\MailchimpSync\External\Subscriber')), 1])->get($listId);

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

    public function subscribe($params = []) {
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
                $this->owner->EUID = $email['euid'];

            $leid = isset($email['leid']) ? $email['leid'] : '';
            $this->owner->addToLists($leid);
        }
    }

    public function unsubscribe($params = []) {
        if($this->owner->{$this->emailField} && ($this->owner->SubcriberListID || $this->owner->MailchimpListID)) {
            \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this->owner)])->unsubscribe(
                array_merge([
                    'email' => $this->owner->{$this->emailField},
                    'list' => $this->owner->SubcriberListID ? $this->owner->SubcriberListID : $this->owner->MailchimpListID,
                ], $params)
            );
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

    public function addToLists($leid = '') {
        if($listId = $this->owner->MailchimpListID) {
            if((strpos($listId, ',') !== false) || is_array($listId)) {
                $listsIds = is_array($listId) ? $listId : explode(',', $listId);
            }
            else
                $listsIds = [$listId];

            foreach($listsIds as $listId) {
                if(!($list = \MailchimpList::get()->filter('MailchimpID', $listId)->first())) {
                    $list = \MailchimpList::create();
                    $list->MailchimpID = $listId;
                    $list->write();
                }

                if($this->owner instanceof \DataObject)
                    $this->owner->Lists()->add($list, ['Subscribed' => \SS_Datetime::now()->Rfc2822(), 'LEID' => $leid]);
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
}
<?php namespace Milkyway\SS\MailchimpSync\Extensions;

use Milkyway\SS\MailchimpSync\Utilities;

class Subscriber extends \DataExtension {
    private static $db = [
        'EUID' => 'Varchar',
    ];

    private static $many_many = [
        'Lists' => 'MailchimpList',
    ];

    private static $many_many_extraFields = [
        'Lists' => [
            'Subscribed' => 'Datetime',
            'LEID' => 'Varchar',
        ],
    ];

    protected $emailField = 'Email';

    public function __construct($type = '', $emailField = 'Email')
    {
        parent::__construct();
        $this->emailField = $emailField;
    }

    public static function get_extra_config($class, $extension, $args) {
        $type = isset($args[0]) ? $args[0] : $class;

        \Config::inst()->update('MailchimpList', 'belongs_many_many', [
                $type => $class,
            ]
        );

        return null;
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

    public function subscribe() {
        if($this->owner->{$this->emailField} && $this->owner->MailchimpListID) {
            $email = \Injector::inst()->createWithArgs('Milkyway\SS\MailchimpSync\Handlers\Subscriber', [Utilities::env_value('Mailchimp_APIKey', $this->owner)])->subscribe(
                [
                    'email' => $this->owner->{$this->emailField},
                    'list' => $this->owner->MailchimpListID,
                    'groups' => $this->owner->MailchimpInterestGroups,
                    'double_optin' => Utilities::env_value('Mailchimp_DoubleOptIn', $this->owner),
                ], $this->owner->MailchimpListParams
            );

            if(isset($email['euid']))
                $this->owner->EUID = $email['euid'];

            $leid = isset($email['leid']) ? $email['leid'] : '';
            $this->owner->addToLists($leid);
        }
    }

    public function unsubscribe() {
        if($this->owner->{$this->emailField} && $this->owner->MailchimpListID) {
            \Injector::inst()->createWithArgs('Milkyway\SS\MailchimpSync\Handlers\Subscriber', [Utilities::env_value('Mailchimp_APIKey', $this->owner)])->unsubscribe(
                [
                    'email' => $this->owner->{$this->emailField},
                    'list' => $this->owner->MailchimpListID,
                ]
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
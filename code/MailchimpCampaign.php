<?php
use Milkyway\SS\MailchimpSync\Handlers\Model\HTTP_Exception;
use Milkyway\SS\MailchimpSync\Utilities;

/**
 * Milkyway Multimedia
 * MailchimpCampaign.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class MailchimpCampaign extends DataObject
{
    private static $singular_name = 'Regular Campaign';

    private static $description = 'Send a HTML Newsletter';

    private static $db = [
        'Subject' => 'Varchar',
        'From' => 'Varchar',
        'FromName' => 'Varchar',

        'Content' => 'HTMLText',

        'Template' => 'Varchar',
    ];

    private static $has_many = [
        'Sent' => 'MailchimpCampaign_SendLog',
        'Scheduled' => 'MailchimpCampaign_Scheduled',
    ];

    private static $summary_fields = [
        'Title',
    ];

    public function getTitle() {
        return $this->Subject;
    }

    public function getCMSFields() {
        $this->beforeExtending('updateCMSFields', function($fields) {
                $fields->removeByName('Template');

                if(!$this->exists())
                    $fields->insertBefore(FormMessageField::create('NOTE-UNSAVED', 'You can start sending this campaign and testing it once it has been saved'), 'Subject');
            }
        );

        $fields = parent::getCMSFields();
        return $fields;
    }
}

class MailchimpCampaign_SendLog extends DataObject {
    private static $singular_name = 'Campaign Sent Item';

    private static $description = 'This is a log for sending a campaign. You can send the same campaign to many different lists etc.';

    private static $db = [
        'Status' => "Enum('save,paused,scheduled,sending,sent')",
        'MailchimpID' => 'Varchar',
        'MailchimpWebID' => 'Varchar',

        'Sent' => 'Datetime',
        'NumberSent' => 'Int',
    ];

    private static $has_one = [
        'Campaign' => 'MailchimpCampaign',
        'Author' => 'Member',
        'List' => 'MailchimpList',
    ];

    private static $summary_fields = [
        'Title',
        'Sent',
        'NumberSent',
    ];

    public function getTitle() {
        return 'Send to ' . $this->List()->Title;
    }

    protected $handler = 'Milkyway\SS\MailchimpSync\Handlers\Campaign';
    protected $listsHandler = 'Milkyway\SS\MailchimpSync\Handlers\Lists';

    private $updated = false;
    private $attemptFix = false;

    public function getCMSFields() {
        $this->beforeExtending('updateCMSFields', function($fields) {
                if(!$this->MailchimpID)
                    $fields->removeByName('MailchimpID');

                if(!$this->MailchimpWebID)
                    $fields->removeByName('MailchimpWebID');

                if(!$this->Status)
                    $fields->removeByName('Status');

                if(!$this->NumberSent)
                    $fields->removeByName('NumberSent');

                if(!$this->Sent)
                    $fields->removeByName('Sent');

                $fields->insertBefore($lists = Select2Field::create('MailingListID', 'Send to:', '',
                        \Injector::inst()->createWithArgs($this->listsHandler, [Utilities::env_value('Mailchimp_APIKey', $this)])->get(), null, 'name', 'id|name'
                    ), 'CampaignID');

                $fields->removeByName('CampaignID');
                $fields->removeByName('AuthorID');
                $fields->removeByName('ListID');

                $lists->requireSelection = true;
                $lists->minSearchLength = 0;
                $lists->suggestURL = false;
            }
        );

        $fields = parent::getCMSFields();
        return $fields;
    }

    public function canEdit($member = null) {
        $this->beforeExtending(__METHOD__, function($member = null) {
                return $this->Status == 'save';
            }
        );

        return parent::canEdit($member);
    }

    public function canSend($member = null) {
        $this->beforeExtending(__METHOD__, function($member = null) {
                return $this->Status == 'save';
            }
        );

        return parent::canEdit($member);
    }

    public function canDelete($member = null) {
        $this->beforeExtending(__METHOD__, function($member = null) {
                return $this->Status == 'save';
            }
        );

        return parent::canEdit($member);
    }

    public function getPostVars() {
        $vars['type'] = 'regular';

        $vars['options']['subject'] = $this->Campaign()->Subject;
        $vars['options']['title'] = $this->Campaign()->Subject;

        $vars['options']['from_email'] = $this->Campaign()->From;
        $vars['options']['from_name'] = $this->Campaign()->FromName;

        $vars['options']['auto_footer'] = false;
        $vars['options']['inline_css'] = true;
        $vars['options']['generate_text'] = true;

        $vars['content']['html'] = $this->Campaign()->Content;
        $vars['options']['list_id'] = $this->ListID;

        $this->extend('updatePostVars', $vars);

        return $vars;
    }

    public function onBeforeWrite() {
        if(!$this->AuthorID)
            $this->AuthorID = Member::currentUserID();

        parent::onBeforeWrite();
    }

    public function onBeforeDelete() {
        parent::onBeforeDelete();
        $this->syncDelete();
    }

    protected function syncCreate() {
        if(!$this->MailchimpID) {
            $campaign = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this)])->create(
                $this->getPostVars()
            );

            if(isset($campaign['id']))
                $this->MailchimpID = $campaign['id'];
            elseif(isset($campaign['web_id']))
                $this->MailchimpWebID = $campaign['web_id'];
        }
    }

    protected function syncDelete() {
        if($this->MailchimpID) {
            \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this)])->delete(
                [
                    'cid'   => $this->MailchimpID,
                ]
            );
        }
    }

    protected function syncUpdate() {
        if($this->MailchimpID && !$this->updated) {
            $vars = $this->getPostVars();

            foreach($vars as $type => $var) {
                if (!is_array($var)) {
                    continue;
                }

                try {
                    $campaign = \Injector::inst()->createWithArgs(
                        $this->handler,
                        [Utilities::env_value('Mailchimp_APIKey', $this)]
                    )->update(
                        [
                            'cid'   => $this->MailchimpID,
                            'name'  => $type,
                            'value' => $var,
                        ]
                    );
                } catch(HTTP_Exception $e) {
                    if(!$this->attemptFix && $e->getMessage() == 'Campaign_DoesNotExist') {
                        $this->attemptFix = true;
                        $this->MailchimpID = null;
                        $this->write();
                    }
                }

                if(isset($campaign['status']))
                    $this->Status = $campaign['status'];
                elseif(isset($campaign['emails_sent']))
                    $this->NumberSent = $campaign['emails_sent'];
            }

            $this->attemptFix = false;
            $this->updated = true;
            $this->write();
        }
    }

    public function saveMailingListID($listId = '') {
        if(strpos($listId, '|') !== false) {
            list($listId, $listName) = explode('|', $listId);
        }
        else {
            $listName = '';
        }

        if(!($list = \MailchimpList::get()->filter('MailchimpID', $listId)->first())) {
            $list = \MailchimpList::create();
            $list->Title = $listName;
            $list->MailchimpID = $listId;
            $list->write();
        }

        $this->ListID = $list->ID;
    }

    public function getMailingListID() {
        return $this->List()->MailchimpID . '|' . $this->List()->Title;
    }
}

class MailchimpCampaign_Scheduled extends DataObject {
    private static $singular_name = 'Scheduled Campaign Notice';

    private static $description = 'You can schedule a campaign to send at a specific time';

    private static $db = [
        'Scheduled' => 'Datetime',
        'Done' => 'Boolean',
    ];

    private static $summary_fields = [
        'Scheduled',
    ];

    private static $has_one = [
        'Campaign' => 'MailchimpCampaign',
        'Log' => 'MailchimpCampaign_SendLog',
        'Author' => 'Member',
    ];
}
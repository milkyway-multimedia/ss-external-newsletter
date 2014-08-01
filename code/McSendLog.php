<?php
/**
 * Milkyway Multimedia
 * McSendLog.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class McSendLog extends DataObject {
    private static $singular_name = 'Send a campaign action';

    private static $description = 'This is a log for sending a campaign. You can send the same campaign to many different lists etc.';

    private static $db = [
        'Status' => "Enum('save,paused,scheduled,sending,sent')",
        'McId' => 'Varchar',
        'McWebId' => 'Varchar',

        'Sent' => 'Datetime',
        'NumberSent' => 'Int',
    ];

    private static $has_one = [
        'Campaign' => 'McCampaign',
        'Author' => 'Member',
        'List' => 'McList',
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
                if(!$this->McId)
                    $fields->removeByName('McId');

                if(!$this->McWebId)
                    $fields->removeByName('McWebId');

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
        if(!$this->McId) {
            $campaign = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this)])->create(
                $this->getPostVars()
            );

            if(isset($campaign['id']))
                $this->McId = $campaign['id'];
            elseif(isset($campaign['web_id']))
                $this->McWebId = $campaign['web_id'];

            $this->write();
        }
    }

    protected function syncDelete() {
        if($this->McId) {
            \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this)])->delete(
                [
                    'cid'   => $this->McId,
                ]
            );
        }
    }

    protected function syncUpdate() {
        if($this->McId && !$this->updated) {
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
                            'cid'   => $this->McId,
                            'name'  => $type,
                            'value' => $var,
                        ]
                    );
                } catch(HTTP_Exception $e) {
                    if(!$this->attemptFix && $e->getMessage() == 'Campaign_DoesNotExist') {
                        $this->attemptFix = true;
                        $this->McId = null;
                        $this->syncCreate();
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

        if(!($list = \McList::get()->filter('McId', $listId)->first())) {
            $list = \McList::create();
            $list->Title = $listName;
            $list->McId = $listId;
            $list->write();
        }

        $this->ListID = $list->ID;
    }

    public function getMailingListID() {
        return $this->List()->McId . '|' . $this->List()->Title;
    }
}
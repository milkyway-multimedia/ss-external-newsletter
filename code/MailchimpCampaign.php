<?php
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
        'MailchimpID' => 'Varchar',
        'MailchimpWebID' => 'Varchar',

        'Status' => "Enum('save,paused,scheduled,sending,sent')",
        'NumberSent' => 'Int',

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

    private static $belongs_many_many = [
        'Lists' => 'MailchimpList',
    ];

    protected $handler = 'Milkyway\SS\MailchimpSync\Handlers\Campaign';
    protected $listsHandler = 'Milkyway\SS\MailchimpSync\Handlers\Lists';

    public function getTitle() {
        return $this->Subject;
    }

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

                $fields->removeByName('Template');

                $fields->insertBefore($lists = Select2Field::create('ListsID', 'List (you can add more lists after you save for the first time)', '',
                        \Injector::inst()->createWithArgs($this->listsHandler, [Utilities::env_value('Mailchimp_APIKey', $this)])->get(), null, 'name', 'id'
                    ), 'Content');

                $lists->requireSelection = true;
                $lists->minSearchLength = 0;
            }
        );

        $fields = parent::getCMSFields();
        return $fields;
    }

    public function onBeforeWrite() {
        if(!$this->MailchimpID) {
            $campaign = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this)])->create(
                $this->getPostVars()
            );

            if(isset($campaign['data'])) {
                if(isset($campaign['data']['id']))
                    $this->MailchimpID = $campaign['data']['id'];
                elseif(isset($campaign['data']['web_id']))
                    $this->MailchimpWebID = $campaign['data']['web_id'];
            }
        }
    }

    public function onAfterWrite() {
        if($this->MailchimpID) {
            $vars = $this->getPostVars();

            foreach($vars as $type => $var) {
                if(!is_array($var)) continue;

                $campaign = \Injector::inst()->createWithArgs($this->handler, [Utilities::env_value('Mailchimp_APIKey', $this)])->update(
                    [
                        'cid' => $this->MailchimpID,
                        'name' => $type,
                        'value' => $var,
                    ]
                );

                if(isset($campaign['data'])) {
                    if(isset($campaign['data']['status']))
                        $this->Status = $campaign['data']['status'];
                    elseif(isset($campaign['data']['emails_sent']))
                        $this->NumberSent = $campaign['data']['emails_sent'];
                }
            }
        }
    }

    public function getPostVars() {
        $vars['type'] = 'regular';

        $vars['options']['subject'] = $this->Subject;

        $vars['options']['from_email'] = $this->From;
        $vars['options']['from_name'] = $this->FromName;

        $vars['options']['auto_footer'] = false;
        $vars['options']['inline_css'] = true;
        $vars['options']['generate_text'] = true;

        $vars['content']['html'] = $this->Content;

        if($this->ListsID)
            $vars['options']['list_id'] = $this->ListsID;

        $this->extend('updatePostVars', $vars);

        return $vars;
    }
}

class MailchimpCampaign_SendLog extends DataObject {
    private static $has_one = [
        'Campaign' => 'MailchimpCampaign',
        'Author' => 'Member',
    ];

    private static $belongs_many_many = [
        'Lists' => 'MailchimpList',
    ];
}

class MailchimpCampaign_Scheduled extends MailchimpCampaign_SendLog {
    private static $db = [
        'Scheduled' => 'Datetime',
    ];
}
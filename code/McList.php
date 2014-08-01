<?php
use Milkyway\SS\MailchimpSync\Utilities;

/**
 * Milkyway Multimedia
 * McList.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class McList extends DataObject
{
    private static $singular_name = 'List';

    private static $db = [
        'Title' => 'Varchar',
        'McId'  => 'Varchar',
    ];

    private static $indexes = array(
        'McId' => true,
    );

    private static $has_many = [
        'Received' => 'McSendLog',
    ];

    protected $listsHandler = 'Milkyway\SS\MailchimpSync\Handlers\Lists';

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
                if ($this->McId) {
                    $fields->addFieldsToTab(
                        'Root.AllEmails',
                        [
                            FormMessageField::create(
                                'NOTE-AllEmails',
                                'This is a list of all emails subscribed to this mailing list from all sources',
                                'info'
                            )->cms(),
                            GridField::create(
                                'AllEmails',
                                'Emails',
                                $this->AllEmails(),
                                GridFieldConfig_RecordEditor::create(50)
                                    ->removeComponentsByType('GridFieldFilterHeader')
                                    ->removeComponentsByType('GridFieldDetailForm')
                                    ->removeComponentsByType('GridFieldDeleteAction')
                                    ->addComponents(new ExternalDataGridFieldDetailForm())
                                    ->addComponents(new ExternalDataGridFieldDeleteAction())
                            )
                        ]
                    );
                }
            }
        );

        $fields = parent::getCMSFields();
        return $fields;
    }

    public function AllEmails()
    {
        return singleton('Milkyway\SS\MailchimpSync\External\Subscriber')->fromMailchimpList($this->McId);
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        // Sync with Mailchimp database
        $lists = \Injector::inst()->createWithArgs($this->listsHandler, [Utilities::env_value('Mailchimp_APIKey', $this)])->get();

        foreach($lists as $list) {
            if(isset($list['id'])) {
                $list['Title'] = (isset($list['name']) ? $list['name'] : '');

                if(static::find_or_make(['McId' => $list['id']], $list)->isNew)
                    \DB::alteration_message((isset($list['name']) ? $list['name'] : $list['id']) . ' List grabbed from Mailchimp', 'created');
            }
        }
    }
} 
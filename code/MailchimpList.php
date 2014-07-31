<?php

/**
 * Milkyway Multimedia
 * MailchimpList.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */
class MailchimpList extends DataObject
{
    private static $db = [
        'Title'       => 'Varchar',
        'MailchimpID' => 'Varchar',
    ];

    private static $indexes = array(
        'MailchimpID' => true,
    );

    private static $has_many = [
        'Received' => 'MailchimpCampaign_SendLog',
    ];

    public function getCMSFields() {
        $this->beforeExtending('updateCMSFields', function($fields) {
                $fields->addFieldsToTab('Root.AllEmails', [
                        FormMessageField::create('NOTE-AllEmails', 'This is a list of all emails subscribed to this mailing list')
                    ]
                );
            }
        );

        $fields = parent::getCMSFields();
        return $fields;
    }
} 
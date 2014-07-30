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

    private static $many_many = [
        'Campaigns' => 'MailchimpCampaign',
        'Sent' => 'MailchimpCampaign_SendLog',
    ];
} 
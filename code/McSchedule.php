<?php
/**
 * Milkyway Multimedia
 * McSchedule.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class McSchedule extends DataObject {
    private static $singular_name = 'Scheduled Date';

    private static $description = 'You can schedule a campaign to send at a specific time';

    private static $db = [
        'Scheduled' => 'Datetime',
        'Done' => 'Boolean',
    ];

    private static $summary_fields = [
        'Scheduled',
    ];

    private static $has_one = [
        'Campaign' => 'McCampaign',
        'Log' => 'McSendLog',
        'Author' => 'Member',
    ];
}
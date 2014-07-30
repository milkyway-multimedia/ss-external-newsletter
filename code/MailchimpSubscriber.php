<?php
/**
 * Milkyway Multimedia
 * MailchimpSubscriber.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class MailchimpSubscriber extends DataObject
{
    private static $db = [
        'FirstName'       => 'Varchar',
        'Surname'       => 'Varchar',
        'Email'       => 'Varchar',
    ];

    private static $has_one = [
        'Member' => 'Member',
    ];

    private static $extensions = [
        'Milkyway\SS\MailchimpSync\Extensions\Subscriber',
    ];

    public function getName() {
        $name = [];

        if($this->owner->Salutation)
            $name[] = $this->owner->Salutation;
        if($this->owner->FirstName)
            $name[] = $this->owner->FirstName;
        if($this->owner->MiddleName)
            $name[] = $this->owner->MiddleName;
        if($this->owner->Surname)
            $name[] = $this->owner->Surname;

        return count($name) ? implode(' ', $name) : '';
    }

    public function setName($name) {
        $fullName = explode(' ', $name);

        if(count($fullName) > 1) {
            $this->owner->Surname = array_pop($fullName);

            if(count($fullName) > 1)
                $this->owner->MiddleName = array_pop($fullName);

            if(count($fullName) > 1) {
                $this->owner->Salutation = array_shift($fullName);
                $this->owner->FirstName = implode(' ', $fullName);
            }
            else
                $this->owner->FirstName = array_pop($fullName);
        }
        else
            $this->owner->FirstName = array_pop($fullName);
    }

    public function getMailchimpMergeVars() {
        return [
            'FirstName' => 'FNAME',
            'Surname' => 'LNAME',
            'Email' => 'EMAIL',
        ];
    }
} 
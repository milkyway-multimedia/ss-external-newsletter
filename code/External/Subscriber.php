<?php namespace Milkyway\SS\ExternalNewsletter\External;
/**
 * Milkyway Multimedia
 * Subscriber.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Subscriber extends \ExternalDataObject {
    private static $singular_name = 'Email';

    private static $extensions = [
        "Milkyway\\SS\\ExternalNewsletter\\Extensions\\Subscriber('AllEmails')",
    ];

    static $db = array(
        'FirstName' => 'Varchar',
        'Surname' => 'Varchar',
        'Email' => 'Varchar',
    );

    private static $summary_fields = [
        'email' => 'Email',
    ];

    protected $handler = 'Milkyway\SS\ExternalNewsletter\Contracts\Subscriber';

    public function getCMSFields() {
        $this->beforeExtending('updateCMSFields', function($fields) {
                if(!$this->ID) {
                    $fields->insertAfter(\CheckboxField::create('DoubleOptIn', 'Send a confirmation email to the user (double opt-in)?', true), 'Email');
                }
            }
        );

        return parent::getCMSFields();
    }

    public function getTitle(){
        return $this->email;
    }

    public function write() {
        $params = [];

        if(!$this->ID)
            $params['double_optin'] = $this->DoubleOptIn;

        $this->subscribeToExternalList($params);
    }

    public function delete() {
        $this->unsubscribeFromExternalList();
    }

    public function getName() {
        $name = [];

        if($this->Salutation)
            $name[] = $this->Salutation;
        if($this->FirstName)
            $name[] = $this->FirstName;
        if($this->MiddleName)
            $name[] = $this->MiddleName;
        if($this->Surname)
            $name[] = $this->Surname;

        return count($name) ? implode(' ', $name) : '';
    }

    public function setName($name) {
        $fullName = explode(' ', $name);

        if(count($fullName) > 1) {
            $this->Surname = array_pop($fullName);
            $this->FirstName = implode(' ', $fullName);
        }
        else
            $this->FirstName = array_pop($fullName);
    }

    public function has_one($component = null) {
        $classes = \ClassInfo::ancestry($this);

        foreach($classes as $class) {
            // Wait until after we reach DataObject
            if(in_array($class, array('Object', 'ViewableData', 'DataObject'))) continue;

            if($component) {
                $hasOne = \Config::inst()->get($class, 'has_one', \Config::UNINHERITED);

                if(isset($hasOne[$component])) {
                    return $hasOne[$component];
                }
            } else {
                $newItems = (array)\Config::inst()->get($class, 'has_one', \Config::UNINHERITED);
                // Validate the data
                foreach($newItems as $k => $v) {
                    if(!is_string($k) || is_numeric($k) || !is_string($v)) {
                        user_error("$class::\$has_one has a bad entry: "
                                   . var_export($k,true). " => " . var_export($v,true) . ".  Each map key should be a"
                                   . " relationship name, and the map value should be the data class to join to.", E_USER_ERROR);
                    }
                }
                $items = isset($items) ? array_merge($newItems, (array)$items) : $newItems;
            }
        }
        return isset($items) ? $items : null;
    }

    public function getMailchimpMergeVars() {
        return [
            'FirstName' => 'FNAME',
            'Surname' => 'LNAME',
            'Email' => 'EMAIL',
        ];
    }
}
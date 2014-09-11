<?php
/**
 * Milkyway Multimedia
 * ExtSubscriber.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class ExtSubscriber extends \DataObject {
    private static $singular_name = 'Subscriber';

    private static $db = array(
        'FirstName' => 'Varchar',
        'Surname' => 'Varchar',
        'Email' => 'Varchar',
    );

	private static $summary_fields = [
		'Name',
		'Email',
	];

	private static $searchable_fields = [
		'FirstName' => 'PartialMatchFilter',
		'Surname' => 'PartialMatchFilter',
		'Email' => 'PartialMatchFilter',
	];

    private static $mailchimp_merge_vars = [
        'FirstName' => 'FNAME',
        'Surname' => 'LNAME',
        'Email' => 'EMAIL',
    ];

    private static $extensions = [
        "Milkyway\\SS\\ExternalNewsletter\\Extensions\\Subscriber('ExtSubscriber')",
    ];

    private static $many_many_extraFields = [
        'Lists' => [
            'DoubleOptIn' => 'Boolean',
        ],
    ];

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
        return $this->ForEmail;
    }

    public function getMailchimpMergeVars() {
        $mergeVars = $this->config()->get('mailchimp_merge_vars', \Config::FIRST_SET);
        $type = 'mailchimp';
        $this->extend('updateMergeVars', $mergeVars, $type);
        return $mergeVars;
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

    public function getForEmail() {
        return $this->Email ? $this->Name ? $this->Name . ' <' . $this->Email . '>' : $this->Email : '';
    }

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();
        $this->sync();
    }

    public function ExtraDataOnSubscription($response = []) {
        $extraData = [
            'Subscribed' => \SS_Datetime::now()->Rfc2822(),
            'LEId' => isset($response['leid']) ? $response['leid'] : '',
        ];

	    if($this->DoubleOptIn !== null)
		    $extraData['DoubleOptIn'] = $this->DoubleOptIn;

        $this->extend('updateExtraDataOnSubscription', $extraData, $response);

        return $extraData;
    }
} 
<?php
/**
 * Milkyway Multimedia
 * Lists.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter\Extensions;


use Milkyway\SS\ExternalNewsletter\Utilities;

class Lists extends \DataExtension {
    private static $db = [
        'ExtId' => 'Varchar',
        'ExtList_Driver' => 'Text',
    ];

    private static $indexes = [
        'ExtId' => true,
    ];

    private static $has_many = [
        'Received' => 'ExtSendLog',
    ];

    public function updateCMSFields(\FieldList $fields) {
        if($this->owner->ExtId) {
            if(!\Permission::check('ADMIN')) {
                $fields->replaceField(
                    'ExtId',
                    \ReadonlyField::create('ExtId', _t('ExternalNewsletter.ExtId', 'Unique ID'))->setDescription(
                        _t(
                            'ExternalNewsletter.DESC-ExtId',
                            'This is the ID of this mailing list that it is tracking on your mailing list provider.'
                        )
                    )
                );
            }
        }
        else
            $fields->removeByName('ExtId');

        if(($received = $fields->dataFieldByName('Received')) && $config = $received->Config) {
            $config->getComponentByType('GridFieldAddNewButton');
            $config->getComponentByType('GridFieldAddNewButton')->setButtonName(_t('ExternalNewsletter.SEND_A_CAMPAIGN_TO_THIS_LIST', 'Send an email campaign to this list'));
        }

        $dataFields = $fields->dataFields();
        $self = $this->owner;
        $callback = function($form, $controller) use ($self) {
            if($lists = $form->Fields()->dataFieldByName('Lists')) {
	            $form->Fields()->replaceField('Lists', $lists->castedCopy('ReadonlyField')->setName('Lists_Readonly')->setValue(implode(', ', $controller->record->Lists()->column('Title'))));
            }
        };

	    $time = 20000;

        foreach($dataFields as $field) {
            if(($field instanceof \GridField) && $detailForm = $field->Config->getComponentByType('GridFieldDetailForm')) {
	            $item = singleton($field->List->dataClass());

	            if($item->hasExtension('Milkyway\SS\ExternalNewsletter\Extensions\Subscriber')) {
		            $item->sync();

		            $field->Config->addComponents(
			            new \GridFieldAjaxRefresh($time)
		            );

		            $detailForm->setItemEditFormCallback($callback);

		            $time += 5000;
	            }
            }
        }
    }

    public function sync($deleteNonExisting = true) {
        // Sync with External Newsletter Database
        $lists = \Injector::inst()->createWithArgs($this->provider, [Utilities::env_value('APIKey', $this->owner)])->get();

        $allowed = Utilities::csv_to_array(Utilities::env_value('AllowedLists'));

        foreach ($lists as $list) {
            if (isset($list['id']) && in_array($list['id'], $allowed)) {
                $list['Title'] = (isset($list['name']) ? $list['name'] : '');

                if ($this->owner->findOrMake(['ExtId' => $list['id']], $list)->isNew && (\Controller::curr() instanceof \DevelopmentAdmin))
                    \DB::alteration_message((isset($list['name']) ? $list['name'] : $list['id']) . ' List grabbed from ' . Utilities::using(), 'created');
            }
        }

        if($deleteNonExisting)
            $this->owner->get()->exclude('ExtId', $allowed)->removeAll();
    }
} 
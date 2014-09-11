<?php
/**
 * Milkyway Multimedia
 * Controller.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter\Extensions;

use Milkyway\SS\ExternalNewsletter\Forms\SubscribeFormField;
use Milkyway\SS\ExternalNewsletter\Utilities;

class Controller extends \Extension {
    private static $allowed_actions = [
        'GlobalSubscribeForm',
    ];

    public function GlobalSubscribeForm() {
        $formClass = Utilities::env_value('GlobalSubscribeForm');
        if(!$formClass) $formClass = 'Form';

        $fields = singleton('ExtSubscriber')->FrontEndFields;
        $actions = singleton('ExtSubscriber')->FrontEndActions;

        $fields->push($ml = SubscribeFormField::create('MailingLists', '', \ExtList::get()));

        if(!$actions->exists())
            $actions->push(\FormAction::create('subscribeExternal', _t('ExternalNewsletter.SUBSCRIBE', 'Subscribe'))->addExtraClass('btn-majorAction'));

        $form = \Object::create($formClass, $this->owner, 'GlobalSubscribeForm', $fields, $actions, singleton('ExtSubscriber')->FrontEndValidator);
        $this->owner->extend('updateGlobalSubscribeForm', $form);

        if(($this->owner instanceof \ContentController) && ($this->owner->data() instanceof \Object))
            $this->owner->data()->extend('updateGlobalSubscribeForm', $form);

        return $form;
    }

    public function subscribeExternal($data, $form, $request) {
        $subscriber = \ExtSubscriber::create();

        $vars = [
            'success' => true,
        ];

        try {
            $form->saveInto($subscriber);
            $subscriber->write();
        } catch (\Exception $e) {
            $vars['success'] = false;
            $vars['message'] = $e->getMessage();
        }

        if($vars['success'])
            $vars['message'] = 'You are now subscribed to our mailing list';

        return $this->respondAccordingly($vars, $form);
    }

    protected function respondAccordingly($params, $form = null){
        if($this->owner->Request->isAjax()) {
            $code = isset($params['success']) ? 200 : 400;
            $status = isset($params['success']) ? 'success' : 'fail';

            $response = new \SS_HTTPResponse(json_encode($params), $code, $status);
            $response->addHeader('Content-type', 'application/json');
            return $response;
        }
        else {
            if($form && isset($params['message'])) {
                $form->sessionMessage($params['message'], isset($params['success']) && $params['success'] ? 'good' : 'bad');
            }

            if(!$this->owner->redirectedTo())
                $this->owner->redirectBack();

	        return true;
        }
    }
} 
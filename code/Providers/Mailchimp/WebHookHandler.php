<?php
/**
 * Milkyway Multimedia
 * WebHookHandler.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

namespace Milkyway\SS\ExternalNewsletter\Providers\Mailchimp;

use Milkyway\SS\ExternalNewsletter\Utilities;

class WebHookHandler
{
    protected $eventMapping = [
        'subscribe'   => 'whitelisted',
        'unsubscribe' => 'blacklisted',
        'cleaned'     => 'blacklisted',
        'upemail'     => 'updated',
        'profile'     => 'updated',
        'campaign'    => 'sent',
    ];

    public function handleWebHook($request)
    {
        if ($request->isHEAD()) {
            return $this->confirmSubscription($request->getBody());
        } else {
            $response  = json_decode($request->getBody(), true);
            $event     = isset($response['event']) ? $response['event'] : 'unknown';
            $messageId = '';
            $email     = '';

            $params = [
                'details'   => isset($response['data']) ? $response['data']: [],
                'timestamp' => isset($response['fired_at']) ? $response['fired_at'] : '',
            ];

            if (count($params['details'])) {
                if (isset($params['details']['_id'])) {
                    $messageId = $params['details']['_id'];
                }

                if (isset($params['details']['email'])) {
                    $email = $params['details']['email'];
                }
            }

            if (!$messageId && isset($response['_id'])) {
                $messageId = $response['_id'];
            }

            if ($event == 'cleaned') {
                $params['permanent'] = true;
            }

            if (isset($this->eventMapping[$event])) {
                $event = $this->eventMapping[$event];
            }

            if (\ClassInfo::exists('Milkyway\SS\Events\Dispatche')) {
                if($event == 'whitelisted' && Utilities::env_value('whitelist_emails_on_subscribe')) {
	                \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('SendThis', 'whitelisted', $messageId, $email, $params, $response);
	                \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('ExternalNewsletter', 'whitelisted', $messageId, $email, $params, $response);
                }

                if($event == 'blacklisted' && Utilities::env_value('blacklist_emails_on_unsubscribe')) {
	                \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('SendThis', 'blacklisted', $messageId, $email, $params, $response);
	                \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('ExternalNewsletter', 'blacklisted', $messageId, $email, $params, $response);
                }
            }
        }
    }

    protected function confirmSubscription($message)
    {
        if (\ClassInfo::exists('Milkyway\SS\Events\Dispatcher')) {
	        \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('SendThis', 'hooked', '', '', ['subject' => 'Subscribed to Mailchimp Web Hook', 'message' => $message]);
	        \Injector::inst()->get('Milkyway\SS\Events\Dispatcher')->fire('ExternalNewsletter', 'hooked', '', '', ['subject' => 'Subscribed to Mailchimp Web Hook', 'message' => $message]);
        }

        return new \SS_HTTPResponse('', 200, 'success');
    }
} 
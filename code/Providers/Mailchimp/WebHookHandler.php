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

            if (\ClassInfo::exists('SendThis')) {
                if($event == 'whitelisted' && Utilities::config()->whitelist_emails_on_subscribe)
                    \SendThis::fire('whitelisted', $messageId, $email, $params, $response);

                if($event == 'blacklisted' && Utilities::config()->blacklist_emails_on_unsubscribe)
                    \SendThis::fire('blacklisted', $messageId, $email, $params, $response);
            }
        }
    }

    protected function confirmSubscription($message)
    {
        if (\ClassInfo::exists('SendThis')) {
            \SendThis::fire('hooked', '', '', ['subject' => 'Subscribed to Mailchimp Web Hook', 'message' => $message]);
        }

        return new \SS_HTTPResponse('', 200, 'success');
    }
} 
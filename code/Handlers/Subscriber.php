<?php namespace Milkyway\SS\MailchimpSync\Handlers;

use Milkyway\SS\MailchimpSync\Handlers\Model\HTTP;

/**
 * Milkyway Multimedia
 * Subscriber.php
 *
 * @package reggardocolaianni.com
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class Subscriber extends HTTP {
    public function get($listId, $status = 'subscribed', $opts = [], $params = []) {
        $params['id'] = $listId;
        $params['status'] = $status;

        if(count($opts))
            $params['opts'] = $opts;

        $response = $this->results($this->endpoint('lists/members.json'), $params);

        if(isset($response['data']))
            return $response['data'];

        return [];
    }

    public function one($euid, $listId, $status = 'subscribed', $opts = [], $params = []) {
        $all = $this->get($listId, $status, $opts, $params);

        if(count($all)) {
            foreach($all as $one) {
                foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($one)) as $key => $value) {
                    if ($euid === $value)
                        return $one;
                }

                return null;
            }
        }

        return null;
    }

    public function subscribe($args = [], $params = []) {
        if(isset($args['email']) && !is_array($args['email'])) {
            $params['email'] = ['email' => $args['email']];

            if(!isset($params['merge_vars']) || !isset($params['merge_vars']['new-email']))
                $params['merge_vars']['new-email'] = $args['email'];
        }

        if(isset($args['groups'])) {
            if(!is_array($args['groups'])) {
                if(strpos($args['groups'], '||') !== false) {
                    $args['groups'] = explode('||', $args['groups']);

                    foreach ($args['groups'] as $key => $group) {
                        if (strpos($group, '|') !== false) {
                            $group = explode('|', $group);

                            $args['groups'][$key] = [
                                'name'   => $group[0],
                                'groups' => strpos($group[1], ',') !== false ? explode(',', $group[1]) : [$group[1]],
                            ];
                        }
                    }
                }
                elseif (strpos($args['groups'], '|') !== false) {
                    $group = explode('|', $args['groups']);

                    $args['groups'] = [[
                        'name'   => $group[0],
                        'groups' => strpos($group[1], ',') !== false ? explode(',', $group[1]) : [$group[1]],
                    ]];
                }
            }
        }

        if(isset($args['groups']) && (!isset($params['merge_vars']) || !isset($params['merge_vars']['groupings'])))
            $params['merge_vars']['groupings'] = $args['groups'];

        $params = array_merge([
                      'id' => $args['list'],
                      'double_optin' => isset($args['double_optin']),
                      'update_existing' => !isset($args['only_allow_new_subscribers']),
                      'replace_interests' => !isset($args['do_not_replace_interests']),
                  ], $params);

        $results = $this->results($this->endpoint('lists/subscribe.json'), $params);
        $this->cleanCache();
        return $results;
    }

    public function unsubscribe($args = [], $params = []) {
        if(isset($args['email']) && !is_array($args['email']))
            $params['email'] = ['email' => $args['email']];

        $params = [
                      'id' => $args['list'],
                      'delete_member' => isset($args['delete_member']),
                      'send_goodbye' => !isset($args['no_notifications']),
                      'send_notify' => !isset($args['no_notifications']),
                  ] + $params;

        $results = $this->results($this->endpoint('lists/unsubscribe.json'), $params);
        $this->cleanCache();
        return $results;
    }
}
<?php
/**
 * Milkyway Multimedia
 * ExtWebHookManager.php
 *
 * @package relatewell.org.au
 * @author Mellisa Hankins <mell@milkywaymultimedia.com.au>
 */

class ExtWebHookManager extends Controller {
    // registered web hook handlers
    private static $web_hooks = [
        'mc' => '\Milkyway\SS\ExternalNewsletter\Providers\Mailchimp\WebHookHandler',
//        'cm' => '\Milkyway\SS\ExternalNewsletter\Providers\CampaignMonitor\WebHookHandler',
    ];

    private static $allowed_actions = [
        'webHook',
    ];

    private static $url_handlers = [
        'POST ' => 'webHook',
        'GET ' => 'webHook',
        'HEAD ' => 'webHook',
    ];

    private static $slug = 'email-campaign-manager';

    function Link($action = '') {
        return Controller::join_links($this->config()->slug, $action);
    }

    function webHook($request) {
        $handlers = $this->config()->web_hooks;
        $hook = $request->param('Hook');

        if(isset($handlers[$hook])) {
            $settings = $handlers[$hook];

            if(is_array($settings) && isset($settings['main'])) {
                list($class, $method) = explode('::', $settings['main']);
            }
            else {
                list($class, $method) = explode('::', $settings);
            }

            if(!$method) $method = 'handleWebHook';

            $response = call_user_func_array([singleton($class), $method], [$request]);

            if($response) return $response;
        }

        $controller = $this->niceView($this);
        $controller->init();

        return $controller->customise([
                'Title' => _t('SendThis.FORBIDDEN', 'Forbidden'),
                'Content' => '<p>Please do not access this page directly</p>',
            ])->renderWith($this->getTemplates());
    }

    protected function niceView(\Controller $controller, $url = '', $action = '') {
        if(ClassInfo::exists('SiteTree')) {
            $page = Page::create();

            $page->URLSegment = $url ? $url : $controller->Link();
            $page->Action = $action;
            $page->ID = -1;

            $controller = Page_Controller::create($page);
        }

        return $controller;
    }

    protected function getTemplates($action = '') {
        $templates = array('ExtWebHookManager', 'Page', 'ContentController');

        if($action) array_unshift($templates, 'ExtWebHookManager_' . $action);

        return $templates;
    }
} 
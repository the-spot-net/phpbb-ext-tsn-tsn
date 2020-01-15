<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/14/20
 * Time: 8:41 PM
 */

namespace tsn\tsn\controller;

use phpbb\auth\auth;
use phpbb\auth\provider_collection;
use phpbb\captcha\factory;
use phpbb\config\config;
use phpbb\content_visibility;
use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\path_helper;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use stdClass;
use Symfony\Component\HttpFoundation\Response;
use tsn\tsn\controller\traits\myspot;
use tsn\tsn\framework\constants\url;

/**
 * Class ajax_controller
 * @package tsn\tsn\controller
 */
class ajax_controller extends AbstractBase
{
    use myspot;

    /** @var stdClass the JSON response */
    private $response = null;

    /**
     * ajax_controller constructor.
     *
     * @param \phpbb\auth\auth                $auth
     * @param \phpbb\auth\provider_collection $authProviderCollection
     * @param \phpbb\captcha\factory          $captcha
     * @param \phpbb\config\config            $config
     * @param \phpbb\content_visibility       $contentVisibility
     * @param \phpbb\db\driver\factory        $db
     * @param \phpbb\controller\helper        $helper
     * @param \phpbb\language\language        $language
     * @param \phpbb\path_helper              $pathHelper
     * @param \phpbb\request\request          $request
     * @param \phpbb\template\template        $template
     * @param \phpbb\user                     $user
     */
    public function __construct(auth $auth, provider_collection $authProviderCollection, factory $captcha, config $config, content_visibility $contentVisibility, \phpbb\db\driver\factory $db, helper $helper, language $language, path_helper $pathHelper, request $request, template $template, user $user)
    {
        parent::__construct($auth, $authProviderCollection, $captcha, $config, $contentVisibility, $db, $helper, $language, $pathHelper, $request, $template, $user);

        $this->response = new stdClass();
        $this->response->status = 0; // 0: error; 1: success, 2: info/warning
        $this->response->data = []; // whatever is necessary
        $this->response->message = null; // message for 0/2 status
    }

    /**
     * @param $route
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @uses url::AJAX_MYSPOT_FEED_PAGE
     */
    public function doIndex($route)
    {
        $this->initUserAuthentication();
        $statusCode = 200;

        switch ($route) {
            case url::AJAX_MYSPOT_FEED_PAGE:
//                $this->response->status = 1;
//                $this->response->data['content'] = '<em>hello world';
//                $this->response->data['page'] = $this->request->variable('page', 0);
                break;
            default:
                $statusCode = 404;
                break;
        }

        $headers = ['Content-Type' => 'application/json'];

        if (!empty($this->user->data['is_bot'])) {
            $headers['X-PHPBB-IS-BOT'] = 'yes';
        }

        return new Response(json_encode($this->response), $statusCode, $headers);
    }
}

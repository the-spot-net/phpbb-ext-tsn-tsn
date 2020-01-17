<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/14/20
 * Time: 8:43 PM
 */

namespace tsn\tsn\controller;

use p_master;
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
use tsn\tsn\controller\traits\users;
use tsn\tsn\framework\constants\url;
use tsn\tsn\framework\logic\query;

/**
 * Class AbstractBase
 * @package tsn\tsn\controller
 */
abstract class AbstractBase
{
    use users;

    /** @var string|null */
    protected static $phpbbRootPath = null;
    /** @var false|string */
    protected static $phpEx = 'php';
    /** @var string */
    protected static $boardUrl;
    /** @var int */
    protected static $now = null;

    /** @var \phpbb\auth\auth */
    protected $auth;
    /** @var \phpbb\auth\provider_collection */
    protected $authProviderCollection;
    /** @var \phpbb\captcha\factory */
    protected $captcha;
    /* @var \phpbb\config\config */
    protected $config;
    /** @var \phpbb\content_visibility */
    protected $contentVisibility;
    /** @var \phpbb\db\driver\driver */
    protected $db;
    /* @var \phpbb\controller\helper */
    protected $helper;
    /** @var \phpbb\language\language */
    protected $language;
    /** @var \phpbb\path_helper */
    protected $pathHelper;
    /** @var \phpbb\request\request */
    protected $request;
    /* @var \phpbb\template\template */
    protected $template;
    /* @var \phpbb\user */
    protected $user;

    /** @var array Store key-value pair vars from modules to compile before output */
    protected $rootVars = [];
    /** @var array Store key-array pair vars from modules to compile before output */
    protected $blockVars = [];

    /**
     * AbstractBase constructor.
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
        $this->auth = $auth;
        $this->authProviderCollection = $authProviderCollection;
        $this->captcha = $captcha;
        $this->config = $config;
        $this->contentVisibility = $contentVisibility;
        $this->db = $db;
        $this->helper = $helper;
        $this->language = $language;
        $this->pathHelper = $pathHelper;
        $this->request = $request;
        $this->template = $template;
        $this->user = $user;

        self::$phpbbRootPath = $this->getConfig('script_path');
        self::$phpEx = substr(strrchr(__FILE__, '.'), 1);
        self::$boardUrl = generate_board_url() . '/';
        self::$now = time();
    }

    /**
     * @param null|string $field
     *
     * @return mixed|\phpbb\config\config
     */
    public function getConfig($field = null)
    {
        return (is_null($field))
            ? $this->config
            : (isset($this->config[$field]))
                ? $this->config[$field]
                : null;
    }

    /**
     * @return \phpbb\db\driver\factory
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return \phpbb\user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets up user permissions commonly for all pages & modules
     */
    protected function initUserAuthentication()
    {
        // Setup the permissions...
        $this->user->session_begin();
        $this->auth->acl($this->user->data);
        $this->user->setup(['viewforum', 'memberlist', 'groups', 'search']);

        $this->template->assign_vars([
            'SERVER_PROTOCOL' => $this->config['server_protocol'],
            'SERVER_DOMAIN'   => $this->config['server_name'],
            'SERVER_PORT'     => (!in_array((int)$this->config['server_port'], [0, 80, 443])) ? ':' . $this->config['server_port'] : '',

            'TIME_NOW' => time(),

            'T_EXT_PATH' => $this->getConfig('script_path') . '/ext/tsn/tsn/styles/all/theme',

            'U_TSN_INDEX' => $this->helper->route(url::ROUTE_INDEX),
            'U_TSN_LOGIN' => $this->helper->route(url::ROUTE_LOGIN),
        ]);

        // Setup constant shell data...
        $this->moduleMiniForums();
        $this->moduleMiniProfile();
    }

    /**
     * Handle the assignment of block vars and root vars before calling the template
     */
    protected function processTemplateVars()
    {
        $this->template->assign_vars($this->rootVars);

        foreach ($this->blockVars as $blockName => $varsArray) {
            $this->template->assign_block_vars_array($blockName, $varsArray);
        }

        $this->rootVars = $this->blockVars = [];
    }

    /**
     * Run the views & comments through the language processor consistently
     *
     * @param int $viewCount
     * @param int $replyCount
     *
     * @return string
     */
    protected function processTopicMeta($viewCount, $replyCount)
    {
        return $this->language->lang('MYSPOT_TOPIC_VIEWS_COMMENTS', number_format((int)$viewCount, 0), number_format((int)$replyCount, 0));
    }

    /**
     * Run the work to generate the user's forum state & data
     */
    private function moduleMiniForums()
    {
        if (!function_exists('display_forums')) {
            include_once('includes/functions_display.' . self::$phpEx);
        }

        display_forums('', $this->config['load_moderators']);

        $this->template->assign_vars([
            'TOTAL_POSTS'             => $this->language->lang('TOTAL_POSTS_COUNT', (int)$this->getConfig('num_posts')),
            'TOTAL_FORUM_POSTS'       => number_format((int)$this->getConfig('num_posts'), 0),
            'TOTAL_TOPICS'            => $this->language->lang('TOTAL_TOPICS', (int)$this->getConfig('num_topics')),
            'TOTAL_FORUM_TOPICS'      => number_format((int)$this->getConfig('num_topics'), 0),
            'TOTAL_USERS'             => $this->language->lang('TOTAL_USERS', (int)$this->getConfig('num_users')),
            'TOTAL_FORUM_USERS'       => number_format((int)$this->getConfig('num_users'), 0),
            'FORUM_IMG'               => $this->user->img('forum_read', 'NO_UNREAD_POSTS'),
            'FORUM_UNREAD_IMG'        => $this->user->img('forum_unread', 'UNREAD_POSTS'),
            'FORUM_LOCKED_IMG'        => $this->user->img('forum_read_locked', 'NO_UNREAD_POSTS_LOCKED'),
            'FORUM_UNREAD_LOCKED_IMG' => $this->user->img('forum_unread_locked', 'UNREAD_POSTS_LOCKED'),
            'S_LOGIN_ACTION'          => append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=login'),
            'U_SEND_PASSWORD'         => ($this->getConfig('email_enable'))
                ? append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=sendpassword')
                : '',
            'S_DISPLAY_BIRTHDAY_LIST' => (bool)$this->getConfig('load_birthdays'),
            'S_INDEX'                 => true, // Not sure what this is for...
            // 'S_MYSPOT_LOGIN_REDIRECT' => '<input type="hidden" name="redirect" value="' . $this->helper->route(url::ROUTE_INDEX) . '">',
            'U_MARK_FORUMS'           => ($this->user->data['is_registered'] || $this->getConfig('load_anon_lastread'))
                ? append_sid(self::$phpbbRootPath . '/index.' . self::$phpEx, 'hash=' . generate_link_hash('global') . '&amp;mark=forums&amp;mark_time=' . time())
                : '',
            'U_MCP'                   => ($this->auth->acl_get('m_') || $this->auth->acl_getf_global('m_'))
                ? append_sid(self::$phpbbRootPath . '/mcp.' . self::$phpEx, 'i=main&amp;mode=front', true, $this->user->session_id)
                : '',
        ]);
    }

    /**
     * Run the work to generate the user's profile data
     */
    private function moduleMiniProfile()
    {
        if ($this->user->data['is_registered']
            && $this->user->data['user_id'] != ANONYMOUS
            && $this->user->data['username']
        ) {

            $this->submoduleSetUserOnlineTime();

            if (!class_exists('p_master')) {
                include_once('includes/functions_module.' . self::$phpEx);
            }

            $module = new p_master();
            $module->list_modules('ucp');
            $module->list_modules('mcp');

            // This is where name, rank and avatar come from...as well as warnings and user notes
            $this->template->assign_vars(phpbb_show_profile($this->user->data, $module->loaded('mcp_notes', 'user_notes'), $module->loaded('mcp_warn', 'warn_user')));
            unset($module);

            // Run the calculations for posts/day & post percent
            $posts_per_day = $this->user->data['user_posts'] / max(1, round((time() - $this->user->data['user_regdate']) / 86400));
            $percentage = ($this->config['num_posts']) ? min(100, ($this->user->data['user_posts'] / $this->config['num_posts']) * 100) : 0;

            $this->template->assign_vars([
                'POSTS_DAY'     => $this->language->lang('POST_DAY', $posts_per_day),
                'POSTS_PCT'     => $this->language->lang('POST_PCT', $percentage),
                'POSTS_DAY_NUM' => number_format($posts_per_day, 2),
                'POSTS_PCT_NUM' => number_format($percentage, 2),

                'U_USER_ADMIN' => ($this->auth->acl_get('a_user'))
                    ? append_sid(self::$phpbbRootPath . '/index.' . self::$phpEx, 'i=users&amp;mode=overview&amp;u=' . $this->user->data['user_id'], true, $this->user->session_id)
                    : '',
            ]);

            // Inactive reason/account?
            if ($this->user->data['user_type'] == USER_INACTIVE) {

                $this->language->add_lang('acp/common');

                switch ($this->user->data['user_inactive_reason']) {
                    case INACTIVE_REGISTER:
                        $inactive_reason = $this->language->lang('INACTIVE_REASON_REGISTER');
                        break;
                    case INACTIVE_PROFILE:
                        $inactive_reason = $this->language->lang('INACTIVE_REASON_PROFILE');
                        break;
                    case INACTIVE_MANUAL:
                        $inactive_reason = $this->language->lang('INACTIVE_REASON_MANUAL');
                        break;
                    case INACTIVE_REMIND:
                        $inactive_reason = $this->language->lang('INACTIVE_REASON_REMIND');
                        break;
                    default:
                        $inactive_reason = $this->language->lang('INACTIVE_REASON_UNKNOWN');
                        break;
                }

                $this->template->assign_vars([
                    'S_USER_INACTIVE'      => true,
                    'USER_INACTIVE_REASON' => $inactive_reason,
                ]);
            }
        }
    }

    /**
     * Updates the user data with the session time duration, if they wish to be tracked as online
     */
    private function submoduleSetUserOnlineTime()
    {
        if ($this->getConfig('load_onlinetrack')) {

            if (!function_exists('phpbb_show_profile')) {
                include_once('includes/functions_display.' . self::$phpEx);
            }

            $sessionRow = query::getUserSessionTime($this->getDb(), $this->user->data['user_id']);

            $this->user->data['session_time'] = (isset($sessionRow['session_time'])) ? $sessionRow['session_time'] : 0;
            $this->user->data['session_viewonline'] = (isset($sessionRow['session_viewonline'])) ? $sessionRow['session_viewonline'] : 0;
        }
    }
}

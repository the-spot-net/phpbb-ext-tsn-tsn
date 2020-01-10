<?php
/**
 * the-spot.net. An extension for the phpBB Forum Software package.
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license       MIT
 */

namespace tsn\tsn\controller;

use p_master;
use phpbb\auth\auth;
use phpbb\auth\provider_collection;
use phpbb\captcha\factory;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\path_helper;
use phpbb\request\request;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\HttpFoundation\Response;
use tsn\tsn\framework\constants\url;
use tsn\tsn\framework\logic\query;

/**
 * the-spot.net main controller.
 */
class main_controller
{
    /** @var \phpbb\auth\auth */
    protected $auth;
    /** @var \phpbb\auth\provider_collection */
    protected $authProviderCollection;
    /** @var \phpbb\captcha\factory */
    protected $captcha;
    /* @var \phpbb\config\config */
    protected $config;
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

    /** @var string */
    private static $boardUrl;
    /** @var string|null */
    private static $phpbbRootPath = null;
    /** @var false|string */
    private static $phpEx = 'php';

    /**
     * main_controller constructor.
     *
     * @param \phpbb\auth\auth                $auth
     * @param \phpbb\auth\provider_collection $authProviderCollection
     * @param \phpbb\captcha\factory          $captcha
     * @param \phpbb\config\config            $config   Config object
     * @param \phpbb\db\driver\factory        $db
     * @param \phpbb\controller\helper        $helper   Controller helper object
     * @param \phpbb\language\language        $language Language object
     * @param \phpbb\path_helper              $pathHelper
     * @param \phpbb\request\request          $request
     * @param \phpbb\template\template        $template Template object
     * @param \phpbb\user                     $user
     */
    public function __construct(auth $auth, provider_collection $authProviderCollection, factory $captcha, config $config, \phpbb\db\driver\factory $db, helper $helper, language $language, path_helper $pathHelper, request $request, template $template, user $user)
    {
        $this->auth = $auth;
        $this->authProviderCollection = $authProviderCollection;
        $this->captcha = $captcha;
        $this->config = $config;
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
    }

    /**
     * @param string $route the Variable URI in /tsn/ajax/{route}
     *
     * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
     * @see \tsn\tsn\framework\constants\url::ROUTE_AJAX_SPECIAL_REPORT
     */
    public function ajax($route)
    {

        switch ($route) {
            case url::SLUG_SPECIAL_REPORT:
                $output = $this->moduleSpecialReport();
                break;
            default:
                $output = new Response('Unsupported route', 404);
                break;
        }

//        $l_message = !$this->config['acme_demo_goodbye'] ? 'DEMO_HELLO' : 'DEMO_GOODBYE';
//        $this->template->assign_var('DEMO_MESSAGE', $this->user->lang($l_message, $name));
//
//        return $this->helper->render('sync_settings.html', $name);

        return $output;
    }

    /**
     * https://outlook.office.com/webhook/78c7947c-7ffe-439a-b3ff-dcaa47e301cf@e80bb92b-bccd-4462-80c7-ead62e3ab04b/IncomingWebhook/23a636d08fef4e55babe020f9334bef4/0c9e2c2d-619f-4085-b58c-04a54ab19289
     * @return \Symfony\Component\HttpFoundation\Response
     * @see \tsn\tsn\controller\url::ROUTE_INDEX
     */
    public function doIndex()
    {
        $this->initUserAuthentication();

        $this->moduleStatistics();

        $this->template->assign_vars([
            'S_ALLOW_MINI_PROFILE'   => !empty($this->config['tsn8_activate_mini_profile']),
            'S_ALLOW_MYSPOT_LOGIN'   => !empty($this->config['tsn8_activate_myspot_login']),
            'S_ALLOW_MINI_FORUMS'    => !empty($this->config['tsn8_activate_mini_forums']),
            'S_ALLOW_SPECIAL_REPORT' => !empty($this->config['tsn8_activate_special_report']),
            'S_ALLOW_NEW_POSTS'      => !empty($this->config['tsn8_activate_newposts']),
            'S_USER_ID'              => $this->user->data['user_id'],
        ]);

        return $this->helper->render('@tsn_tsn/tsn_myspot.html', $this->language->lang('MYSPOT'), 200, true);
    }

    /**
     * Renders and processes the login form/submission
     */
    public function doLogin()
    {
        if ($this->user->data['is_registered']) {
            redirect(append_sid($this->helper->route(url::ROUTE_INDEX)));
        }

        $this->initUserAuthentication();

        return $this->moduleLogin($this->request->variable('redirect', $this->helper->route(url::ROUTE_INDEX)));
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
     * @return \phpbb\db\driver\driver|\phpbb\db\driver\factory
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Run the submodule work for a User Avatar
     *
     * @param int $userId
     *
     * @return string
     */
    private function generateUserAvatar(int $userId)
    {
        $avatarImage = '';
        if ($avatarRow = query::getUserAvatar($this, $userId)) {

            // Prepare the avatar image...
            $avatarImage = preg_replace('/(\.\.\/)+?/', './', phpbb_get_user_avatar([
                'avatar'        => $avatarRow['user_avatar'],
                'avatar_type'   => $avatarRow['user_avatar_type'],
                'avatar_width'  => $avatarRow['user_avatar_width'],
                'avatar_height' => $avatarRow['user_avatar_height'],
            ]));
        }

        return $avatarImage;
    }

    /**
     * Sets up user permissions commonly for all pages & modules
     */
    private function initUserAuthentication()
    {
        // Setup the permissions...
        $this->user->session_begin();
        $this->auth->acl($this->user->data);
        $this->user->setup(['viewforum', 'memberlist', 'groups']);

        $this->template->assign_vars([
            'SERVER_PROTOCOL' => $this->config['server_protocol'],
            'SERVER_DOMAIN'   => $this->config['server_name'],
            'SERVER_PORT'     => (!in_array((int)$this->config['server_port'], [0, 80, 443])) ? ':' . $this->config['server_port'] : '',

            'T_EXT_PATH' => $this->getConfig('script_path') . '/ext/tsn/tsn/styles/all/theme',

            'U_TSN_INDEX' => $this->helper->route(url::ROUTE_INDEX),
            'U_TSN_LOGIN' => $this->helper->route(url::ROUTE_LOGIN),
        ]);

        // Setup constant shell data...
        $this->moduleMiniForums();
        $this->moduleMiniProfile();
    }

    /**
     * @param string $redirect Failure Redirect URL (Overwritten on success by request variable/default)
     *
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    private function moduleLogin($redirect)
    {
        $err = '';
        $form_name = 'login';

        // Make sure setup() is called...
        if (!$this->user->is_setup()) {
            $this->user->setup();
        }

        // Skip Admin Login logic for now...

        if ($this->request->is_set_post('login') || ($this->request->is_set('login') && $this->request->variable('login', '') == 'external')) {

            // Skip Admin Login logic for now...

            // Get the credential...
            $password = $this->request->untrimmed_variable('password', '', true);
            $username = $this->request->variable('username', '', true);
            $autologin = $this->request->is_set_post('autologin');
            $viewonline = (int)!$this->request->is_set_post('viewonline');

            // Skip Admin Login logic for now...

            // Check form key
            if ($password && !defined('IN_CHECK_BAN') && !check_form_key($form_name)) {
                $result = [
                    'status'    => false,
                    'error_msg' => 'FORM_INVALID',
                ];
            } else {
                // If authentication is successful we redirect user to previous page
                // `false` should be $isAdmin bool
                $result = $this->auth->login($username, $password, $autologin, $viewonline, false);
            }

            if ($result['status'] == LOGIN_SUCCESS) {
                // Special case... the user is effectively banned, but we allow founders to login
                if (defined('IN_CHECK_BAN') && $result['user_row']['user_type'] != USER_FOUNDER) {
                    return;
                }

                // append/replace SID (may change during the session for AOL users)
                redirect(reapply_sid($this->request->variable('redirect', $this->helper->route(url::ROUTE_INDEX))));

            } else {
                // Something failed, determine what...
                // Special cases...
                switch ($result['status']) {
                    case LOGIN_ERROR_PASSWORD_CONVERT:
                        $err = sprintf(
                            $this->language->lang($result['error_msg']),
                            // TODO - Update this route to /tsn/user/send-password
                            ($this->getConfig('email_enable')) ? '<a href="' . append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=sendpassword') . '">' : '',
                            ($this->getConfig('email_enable')) ? '</a>' : '',
                            '<a href="' . phpbb_get_board_contact_link($this->config, self::$phpbbRootPath, self::$phpEx) . '">',
                            '</a>'
                        );
                        break;
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case LOGIN_ERROR_ATTEMPTS:

                        $captcha = $this->captcha->get_instance($this->getConfig('captcha_plugin'));
                        $captcha->init(CONFIRM_LOGIN);
                        // $captcha->reset();

                        $this->template->assign_vars([
                            'CAPTCHA_TEMPLATE' => $captcha->get_template(),
                        ]);
                    // no break;

                    // Username, password, etc...
                    default:
                        $err = $this->language->lang($result['error_msg']);

                        // Assign admin contact to some error messages
                        if ($result['error_msg'] == 'LOGIN_ERROR_USERNAME' || $result['error_msg'] == 'LOGIN_ERROR_PASSWORD') {
                            // TODO - Update this route with /tsn/contact/admin
                            $err = sprintf($this->language->lang($result['error_msg']), '<a href="' . append_sid(self::$phpbbRootPath . 'memberlist.' . self::$phpEx, 'mode=contactadmin') . '">', '</a>');
                        }

                        break;
                }
            }
        }

        $s_hidden_fields = [
            'sid' => $this->user->session_id,
        ];

        // Skip Admin login hidden fields

        /** @var \phpbb\auth\provider\provider_interface $auth_provider */
        $auth_provider = $this->authProviderCollection->get_provider();

        if ($auth_provider_data = $auth_provider->get_login_data()) {
            if (isset($auth_provider_data['VARS'])) {
                $this->template->assign_vars($auth_provider_data['VARS']);
            }

            if (isset($auth_provider_data['BLOCK_VAR_NAME'])) {
                foreach ($auth_provider_data['BLOCK_VARS'] as $block_vars) {
                    $this->template->assign_block_vars($auth_provider_data['BLOCK_VAR_NAME'], $block_vars);
                }
            }

            $this->template->assign_vars([
                'PROVIDER_TEMPLATE_FILE' => $auth_provider_data['TEMPLATE_FILE'],
            ]);
        }

        $s_hidden_fields = build_hidden_fields($s_hidden_fields);

        $this->template->assign_vars([
            'LOGIN_ERROR' => $err,
            //            'LOGIN_EXPLAIN' => $l_explain,

            'U_SEND_PASSWORD'     => ($this->getConfig('email_enable'))
                // TODO - Update this route to /tsn/user/send-password
                ? append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=sendpassword')
                : '',
            'U_RESEND_ACTIVATION' => ($this->getConfig('require_activation') == USER_ACTIVATION_SELF && $this->getConfig('email_enable'))
                // TODO - Update this route to /tsn/user/send-activation
                ? append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=resend_act')
                : '',
            // TODO - Update this route to /tsn/terms
            'U_TERMS_USE'         => append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=terms'),
            // TODO - Update this route to /tsn/privacy
            'U_PRIVACY'           => append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=privacy'),
            // TODO - Update this route to /tsn/privacy
            'UA_PRIVACY'          => addslashes(append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=privacy')),

            'S_DISPLAY_FULL_LOGIN' => true,
            'S_HIDDEN_FIELDS'      => $s_hidden_fields,

            'S_ADMIN_AUTH' => false, // $admin, // Skip admin login for now
            'USERNAME'     => '', // ($admin) ? $user->data['username'] : '', // Skip admin login for now

            'USERNAME_CREDENTIAL' => 'username',
            'PASSWORD_CREDENTIAL' => 'password', // ($admin) ? 'password_' . $credential : 'password', // Skip admin login for now
        ]);

        return $this->helper->render('@tsn_tsn/tsn_login.html', $this->language->lang('LOGIN'));
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
            'S_LOGIN_ACTION'          => append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=login'),
            'U_SEND_PASSWORD'         => ($this->getConfig('email_enable'))
                ? append_sid(self::$phpbbRootPath . 'ucp.' . self::$phpEx, 'mode=sendpassword')
                : '',
            'S_DISPLAY_BIRTHDAY_LIST' => (bool)$this->getConfig('load_birthdays'),
            'S_INDEX'                 => true, // Not sure what this is for...
            // 'S_MYSPOT_LOGIN_REDIRECT' => '<input type="hidden" name="redirect" value="' . $this->helper->route(url::ROUTE_INDEX) . '">',
            'U_MARK_FORUMS'           => ($this->user->data['is_registered'] || $this->getConfig('load_anon_lastread'))
                ? append_sid(self::$phpbbRootPath . 'index.' . self::$phpEx, 'hash=' . generate_link_hash('global') . '&amp;mark=forums&amp;mark_time=' . time())
                : '',
            'U_MCP'                   => ($this->auth->acl_get('m_') || $this->auth->acl_getf_global('m_'))
                ? append_sid(self::$phpbbRootPath . 'mcp.' . self::$phpEx, 'i=main&amp;mode=front', true, $this->user->session_id)
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
                    ? append_sid(self::$phpbbRootPath . 'index.' . self::$phpEx, 'i=users&amp;mode=overview&amp;u=' . $this->user->data['user_id'], true, $this->user->session_id)
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
     * Handles the render of the Special Report AJAX request from tsn/special-report
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function moduleSpecialReport()
    {
        $this->initUserAuthentication();

        // Get the minimal Latest Topic Info, if any
        if ($topicRow = query::checkForSpecialReportLatestTopic($this)) {

            // Get the necessary topic info, since it exists

            if ($topicRow = query::getSpecialReportLatestTopicInfo($this, $topicRow['topic_id'])) {

                /**
                 * Available Variable List:
                 * $topicRow['topic_id'];
                 * $topicRow['post_id'];
                 * $topicRow['topic_title'];
                 * $topicRow['topic_poster'];
                 * $topicRow['post_text'];
                 * $topicRow['bbcode_uid'];
                 * $topicRow['bbcode_bitfield'];
                 * $topicRow['enable_smilies'];
                 * $topicRow['poster_id'];
                 * $topicRow['username'];
                 * $topicRow['topic_views'];
                 * $topicRow['topic_posts_approved'];
                 * $topicRow['topic_time'];
                 */

                $topicStatusRow = query::getTopicReadStatus($this, $this->user->data['user_id'], $topicRow['topic_id'], $this->config['tsn_specialreport_forumid']);

                // Determine if this is an unread topic, based on the timestamps
                $markTime = $topicStatusRow['f_mark_time'];
                $rowSet = [
                    $topicRow['topic_id'] => $topicStatusRow,
                    'mark_time'           => $markTime,
                ];

                $topicTrackingInfo = get_topic_tracking($this->config['tsn_specialreport_forumid'], [$topicRow['topic_id']], $rowSet, [$this->config['tsn_specialreport_forumid'] => $markTime]);
                $isUnreadTopic = (isset($topicTrackingInfo[$topicRow['topic_id']]) && $topicRow['topic_time'] > $topicTrackingInfo[$topicRow['topic_id']]);

                // Prepare the post content; Replaces UIDs with BBCode and then convert the Post Content to an excerpt...
                $words = explode(' ', generate_text_for_display($topicRow['post_text'], $topicRow['bbcode_uid'], $topicRow['bbcode_bitfield'], 1));

                $this->template->assign_block_vars('specialreport', [
                    'I_AVATAR_IMG' => $this->generateUserAvatar($topicRow['topic_poster']),

                    'L_HEADLINE'         => censor_text($topicRow['topic_title']),
                    'L_POST_AUTHOR'      => get_username_string('full', $topicRow['topic_poster'], $topicRow['username'], $topicRow['user_colour']),
                    'L_POST_BODY'        => (count($words) > $this->config['tsn_specialreport_excerpt_words'])
                        ? implode(' ', array_slice($words, 0, $this->config['tsn_specialreport_excerpt_words'])) . '... '
                        : implode(' ', $words),
                    'L_POST_DATE'        => $this->user->format_date($topicRow['topic_time']),
                    'L_POST_META'        => $this->language->lang('SPECIAL_REPORT_VIEWS_COMMENTS_COUNT', $topicRow['topic_views'], (int)$topicRow['topic_posts_approved'] - 1),
                    'S_UNREAD_TOPIC'     => $isUnreadTopic,
                    'U_CONTINUE_READING' => append_sid(self::$phpbbRootPath . 'viewtopic.' . self::$phpEx, "p=" . $topicRow['post_id']),
                    'U_HEADLINE'         => append_sid(self::$phpbbRootPath . 'viewtopic.' . self::$phpEx, "p=" . $topicRow['post_id']),
                    'U_USER_PROFILE'     => append_sid(self::$phpbbRootPath . 'memberlist.' . self::$phpEx, "mode=viewprofile&u=" . $topicRow['topic_poster']),
                ]);

                $output = $this->helper->render('@tsn_tsn/tsn_special_report.html', $this->language->lang('MYSPOT_SPECIAL_REPORT'));

            } else {
                $output = new Response('Could not find topic with the requested topic ID', 200);
            }
        } else {
            $output = new Response('No topics posted to the Special Report forum', 200);
        }

        return $output;
    }

    /**
     * Call the submodules for Statistics sidebar
     */
    private function moduleStatistics()
    {
        $this->submoduleUserBirthdays();
        $this->submoduleUsersOnline();
        $this->submoduleUserGroupLegend();
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

            $sessionRow = query::getUserSessionTime($this, $this->user->data['user_id']);

            $this->user->data['session_time'] = (isset($sessionRow['session_time'])) ? $sessionRow['session_time'] : 0;
            $this->user->data['session_viewonline'] = (isset($sessionRow['session_viewonline'])) ? $sessionRow['session_viewonline'] : 0;
        }
    }

    /**
     * Generates the Birthday List, if required.
     */
    private function submoduleUserBirthdays()
    {
        if ($this->getConfig('load_birthdays') && $this->getConfig('allow_birthdays') && $this->auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel')) {

            $time = $this->user->create_datetime();
            $cursor = query::getUserBirthdaysCursor($this, $time);

            while ($userRow = $this->db->sql_fetchrow($cursor)) {

                // Some users may not put a year in their profile
                $birthday_year = (int)substr($userRow['user_birthday'], -4);

                $this->template->assign_block_vars('birthdays', [
                    'AGE'          => ($birthday_year) ? max(0, (int)$time->format('Y') - $birthday_year) : '',
                    'I_AVATAR_IMG' => $this->generateUserAvatar($userRow['user_id']),
                    'NAME'         => $userRow['username'],
                    'COLOR'        => ($userRow['user_colour']) ? '#' . $userRow['user_colour'] : '',
                    'URL'          => $this->helper->route(url::make(url::ROUTE_MEMBER, [$userRow['user_id']])),
                ]);
            }

            query::freeCursor($this, $cursor);
        }
    }

    /**
     * Generate a Template Block Var array of User Group Legend Data
     */
    private function submoduleUserGroupLegend()
    {
        $cursor = query::getUserGroupLegendCursor($this, $this->user->data['user_id'], $this->auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel'));

        while ($userGroupRow = $this->db->sql_fetchrow($cursor)) {

            $showUrl = ($userGroupRow['group_name'] != 'BOTS' && ($this->user->data['user_id'] != ANONYMOUS && $this->auth->acl_get('u_viewprofile')));

            // Add the User Group Legends to a block var in a loop
            $this->template->assign_block_vars('usergroups', [
                'COLOR' => ($userGroupRow['group_colour']) ? '#' . $userGroupRow['group_colour'] : '',
                'NAME'  => ($userGroupRow['group_type'] == GROUP_SPECIAL)
                    ? $this->language->lang('G_' . $userGroupRow['group_name'])
                    : $userGroupRow['group_name'],
                'URL'   => ($showUrl)
                    ? append_sid(self::$phpbbRootPath . url::ROUTE_GROUP . '/' . $userGroupRow['group_id'])
                    : '',
            ]);
        }
        query::freeCursor($this, $cursor);
    }

    /**
     * Generate the block variables for online users
     */
    private function submoduleUsersOnline()
    {
        $online_users = obtain_users_online();

        $this->template->assign_vars([
            'TOTAL_USERS_VALUE'   => $online_users['total_online'],
            'VISIBLE_USERS_VALUE' => $online_users['visible_online'],
            'HIDDEN_USERS_VALUE'  => $online_users['hidden_online'],
            'GUEST_USERS_VALUE'   => $online_users['guests_online'],

            'NEWEST_USER_AVATAR' => $this->generateUserAvatar($this->getConfig('newest_user_id')),
            'NEWEST_USER_NAME'   => get_username_string('full', $this->getConfig('newest_user_id'), $this->getConfig('newest_username'), $this->getConfig('newest_user_colour')),

            'ONLINE_RECORD_COUNT' => $this->getConfig('record_online_users'),
            'ONLINE_RECORD_DATE'  => $this->user->format_date($this->getConfig('record_online_date'), false, true),
        ]);
    }
}

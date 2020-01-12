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
use phpbb\content_visibility;
use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\path_helper;
use phpbb\request\request;
use phpbb\request\request_interface;
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
     * @param \phpbb\cache\service            $cacheService
     * @param \phpbb\captcha\factory          $captcha
     * @param \phpbb\config\config            $config   Config object
     * @param \phpbb\content_visibility       $contentVisibility
     * @param \phpbb\db\driver\factory        $db
     * @param \phpbb\controller\helper        $helper   Controller helper object
     * @param \phpbb\language\language        $language Language object
     * @param \phpbb\path_helper              $pathHelper
     * @param \phpbb\request\request          $request
     * @param \phpbb\template\template        $template Template object
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
    }

    /**
     * @param string $route the Variable URI in /tsn/ajax/{route}
     *
     * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
     */
    public function ajax($route)
    {

        switch ($route) {
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
        $this->moduleSpecialReport();
        $this->moduleMySpotPosts();

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

        $this->moduleLogin($this->request->variable('redirect', $this->helper->route(url::ROUTE_INDEX)));

        return $this->helper->render('@tsn_tsn/tsn_login.html', $this->language->lang('LOGIN'));
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
     * @return \phpbb\user
     */
    public function getUser()
    {
        return $this->user;
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
        $this->user->setup(['viewforum', 'memberlist', 'groups', 'search']);

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
                            ($this->getConfig('email_enable')) ? '<a href="' . append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=sendpassword') . '">' : '',
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
                            $err = sprintf($this->language->lang($result['error_msg']), '<a href="' . append_sid(self::$phpbbRootPath . '/memberlist.' . self::$phpEx, 'mode=contactadmin') . '">', '</a>');
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
                ? append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=sendpassword')
                : '',
            'U_RESEND_ACTIVATION' => ($this->getConfig('require_activation') == USER_ACTIVATION_SELF && $this->getConfig('email_enable'))
                // TODO - Update this route to /tsn/user/send-activation
                ? append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=resend_act')
                : '',
            // TODO - Update this route to /tsn/terms
            'U_TERMS_USE'         => append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=terms'),
            // TODO - Update this route to /tsn/privacy
            'U_PRIVACY'           => append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=privacy'),
            // TODO - Update this route to /tsn/privacy
            'UA_PRIVACY'          => addslashes(append_sid(self::$phpbbRootPath . '/ucp.' . self::$phpEx, 'mode=privacy')),

            'S_DISPLAY_FULL_LOGIN' => true,
            'S_HIDDEN_FIELDS'      => $s_hidden_fields,

            'S_ADMIN_AUTH' => false, // $admin, // Skip admin login for now
            'USERNAME'     => '', // ($admin) ? $user->data['username'] : '', // Skip admin login for now

            'USERNAME_CREDENTIAL' => 'username',
            'PASSWORD_CREDENTIAL' => 'password', // ($admin) ? 'password_' . $credential : 'password', // Skip admin login for now
        ]);
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
     * Generate the post sets for the MySpot page
     */
    private function moduleMySpotPosts()
    {

        $this->template->assign_vars([
            'S_ALLOW_SEARCH'      => ($this->auth->acl_get('u_search') || $this->auth->acl_getf_global('f_search') || $this->getConfig('load_search')),
            'S_SEARCH_OVERLOADED' => ($this->user->load && $this->getConfig('limit_search_load') && ($this->user->load > doubleval($this->getConfig('limit_search_load')))),
        ]);

        // This was a normal search feature, but is new for tsn9 prominence
        $this->submoduleNewPosts();
        // This is the original "what's new" module
        $this->submoduleUnreadPosts();
        // This is to keep conversations going
        $this->submoduleUnansweredTopics();
        // This is to show what's popular, and recently replied to
        $this->submoduleActiveTopics();

    }

    /**
     * Handles the render of the Special Report AJAX request from tsn/special-report
     */
    private function moduleSpecialReport()
    {
        // Get the minimal Latest Topic Info, if any
        if ($topicRow = query::checkForSpecialReportLatestTopic($this)) {

            // Get the necessary topic info, since it exists

            if ($topicRow = query::getSpecialReportLatestTopicInfo($this, $topicRow['topic_id'])) {

                /**
                 * Available Variable List:
                 * $topicRow['forum_name'];
                 * $topicRow['forum_id'];
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
                $postBody = generate_text_for_display($topicRow['post_text'], $topicRow['bbcode_uid'], $topicRow['bbcode_bitfield'], 1);
                $postWords = explode(' ', strip_tags($postBody));

                $this->template->assign_block_vars('specialreport', [
                    'FORUM_NAME' => $topicRow['forum_name'],

                    'TOPIC_ID'   => $topicRow['topic_id'],
                    'TOPIC_META' => $this->processTopicMeta($topicRow['topic_views'], (int)$topicRow['topic_posts_approved'] - 1),

                    'HEADLINE'           => censor_text($topicRow['topic_title']),
                    'I_AVATAR_IMG'       => $this->generateUserAvatar($topicRow['topic_poster']),
                    // TODO Update this to use new route URL
                    'POST_AUTHOR'        => get_username_string('full', $topicRow['topic_poster'], $topicRow['username'], $topicRow['user_colour']),
                    'POST_EXCERPT'       => implode(' ', array_slice($postWords, 0, 200)),
                    'POST_BODY'          => $postBody,
                    'POST_DATE'          => $this->user->format_date($topicRow['topic_time']),
                    'S_UNREAD_TOPIC'     => $isUnreadTopic,
                    'U_CONTINUE_READING' => $this->helper->route(url::ROUTE_POST, ['id' => $topicRow['post_id']]),
                    'U_FORUM'            => $this->helper->route(url::ROUTE_FORUM, ['id' => $topicRow['forum_id']]),
                ]);
            }
        }
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
     * Process the topic query for the particular submodule results cursor
     *
     * @param $blockName
     * @param $cursor
     * @param $field
     * @param $forumIdExclusions
     * @param $m_approve_topics_fid_sql
     */
    private function postprocessMySpotSearchResults($cursor, $field)
    {
        $start = 0;
        $per_page = 10; // $this->getConfig('topics_per_page');

        $id_ary = [];
        while ($row = $this->db->sql_fetchrow($cursor)) {
            $id_ary[] = (int)$row[$field];
        }
        query::freeCursor($this, $cursor);

        if ($total_match_count = count($id_ary)) {
            // We have matches, take the first page
            $id_ary = array_slice($id_ary, $start, $per_page);
        }

        return $id_ary;
    }

    private function prepareMySpotSearchResultsOutput($blockName, $id_ary, $forumIdExclusions, $approvedTopicForumIdsSql)
    {
        // make sure that some arrays are always in the same order
        sort($forumIdExclusions);

        $this->language->add_lang('viewtopic');

        if (count($id_ary)) {

            // Do this for later...
            if ($this->getConfig('load_anon_lastread') || ($this->user->data['is_registered'] && !$this->getConfig('load_db_lastread'))) {
                $tracking_topics = $this->request->variable($this->getConfig('cookie_name') . '_track', '', true, request_interface::COOKIE);
                $tracking_topics = ($tracking_topics) ? tracking_unserialize($tracking_topics) : [];
            }

            $topic_tracking_info = $forums = $rowset = $shadow_topic_list = [];

            $cursor = query::getMySpotNewPostsTopicDetailsCursor($this, $id_ary, $forumIdExclusions, $approvedTopicForumIdsSql);

            while ($row = $this->db->sql_fetchrow($cursor)) {

                $row['forum_id'] = (int)$row['forum_id'];
                $row['topic_id'] = (int)$row['topic_id'];

                if ($row['topic_status'] == ITEM_MOVED) {
                    $shadow_topic_list[$row['topic_moved_id']] = $row['topic_id'];
                }

                $rowset[$row['topic_id']] = $row;

                if (!isset($forums[$row['forum_id']]) && $this->user->data['is_registered'] && $this->getConfig('load_db_lastread')) {
                    $forums[$row['forum_id']]['mark_time'] = $row['f_mark_time'];
                }
                $forums[$row['forum_id']]['topic_list'][] = $row['topic_id'];
                $forums[$row['forum_id']]['rowset'][$row['topic_id']] = &$rowset[$row['topic_id']];
            }
            query::freeCursor($this, $cursor);

            // If we have some shadow topics, update the rowset to reflect their topic information
            if (count($shadow_topic_list)) {

                $cursor = query::getTopicRowCursor($this, $shadow_topic_list);
                while ($row = $this->db->sql_fetchrow($cursor)) {

                    $orig_topic_id = $shadow_topic_list[$row['topic_id']];

                    // We want to retain some values
                    $row = array_merge($row, [
                            'topic_moved_id' => $rowset[$orig_topic_id]['topic_moved_id'],
                            'topic_status'   => $rowset[$orig_topic_id]['topic_status'],
                            'forum_name'     => $rowset[$orig_topic_id]['forum_name'],
                        ]
                    );

                    $rowset[$orig_topic_id] = $row;
                }
                query::freeCursor($this, $cursor);
            }
            unset($shadow_topic_list);

            foreach ($forums as $forum_id => $forum) {
                if ($this->user->data['is_registered'] && $this->getConfig('load_db_lastread')) {
                    $topic_tracking_info[$forum_id] = get_topic_tracking($forum_id, $forum['topic_list'], $forum['rowset'], [$forum_id => $forum['mark_time']]);
                } else if ($this->getConfig('load_anon_lastread') || $this->user->data['is_registered']) {
                    $topic_tracking_info[$forum_id] = get_complete_topic_tracking($forum_id, $forum['topic_list']);

                    if (!$this->user->data['is_registered']) {
                        $this->user->data['user_lastmark'] = (isset($tracking_topics['l']))
                            ? (int)(base_convert($tracking_topics['l'], 36, 10) + $this->getConfig('board_startdate'))
                            : 0;
                    }
                }
            }
            unset($forums);

            if (!function_exists('topic_status')) {
                include_once('includes/functions_display.' . self::$phpEx);
            }

            foreach ($rowset as $row) {
                $forum_id = $row['forum_id'];
                $topicId = $row['topic_id'];
                $replies = $this->contentVisibility->get_count('topic_posts', $row, $forum_id) - 1;

                $folder_img = $folder_alt = $topic_type = '';
                topic_status($row, $replies, (isset($topic_tracking_info[$forum_id][$topicId]) && $row['topic_last_post_time'] > $topic_tracking_info[$forum_id][$topicId]) ? true : false, $folder_img, $folder_alt, $topic_type);

                $unread_topic = (isset($topic_tracking_info[$forum_id][$topicId]) && $row['topic_last_post_time'] > $topic_tracking_info[$forum_id][$topicId]) ? true : false;

                $topic_unapproved = (($row['topic_visibility'] == ITEM_UNAPPROVED || $row['topic_visibility'] == ITEM_REAPPROVE) && $this->auth->acl_get('m_approve', $forum_id)) ? true : false;
                $posts_unapproved = ($row['topic_visibility'] == ITEM_APPROVED && $row['topic_posts_unapproved'] && $this->auth->acl_get('m_approve', $forum_id)) ? true : false;
                $topic_deleted = ($row['topic_visibility'] == ITEM_DELETED);

                $postBody = generate_text_for_display($row['post_text'], $row['bbcode_uid'], $row['bbcode_bitfield'], 1);
                $postWords = explode(' ', strip_tags($postBody));

                $this->template->assign_block_vars($blockName, [
                    'FORUM_TITLE' => $row['forum_name'],

                    'TOPIC_META' => $this->processTopicMeta($row['topic_views'], (int)$row['topic_posts_approved'] - 1),

                    'LAST_POST_SUBJECT'         => $row['topic_last_post_subject'],
                    'LAST_POST_TIME'            => $this->user->format_date($row['topic_last_post_time']),
                    'LAST_POST_AUTHOR_FULL'     => get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
                    'I_LAST_POST_AUTHOR_AVATAR' => $this->generateUserAvatar($row['topic_last_poster_id']),
                    'LAST_POST_EXCERPT'         => implode(' ', array_slice($postWords, 0, 100)),

                    'S_UNREAD_TOPIC' => $unread_topic,

                    'S_TOPIC_REPORTED'   => (!empty($row['topic_reported']) && $this->auth->acl_get('m_report', $forum_id)) ? true : false,
                    'S_TOPIC_UNAPPROVED' => $topic_unapproved,
                    'S_POSTS_UNAPPROVED' => $posts_unapproved,
                    'S_TOPIC_DELETED'    => $topic_deleted,
                    'S_HAS_POLL'         => ($row['poll_start']) ? true : false,

                    'U_NEWEST_POST' => $this->helper->route(url::ROUTE_TOPIC, ['id' => $topicId, 'f' => $forum_id, 'view' => 'unread']) . '#unread',
                    // TODO Update this to MCP Route, when exists
                    'U_MCP_REPORT'  => append_sid(self::$phpbbRootPath . '/mcp.' . self::$phpEx, 'i=reports&amp;mode=reports&amp;t=' . $topicId, true, $this->user->session_id),
                    'U_VIEW_FORUM'  => $this->helper->route(url::ROUTE_FORUM, ['id' => $forum_id]),
                ]);
            }
        }
        unset($rowset);
    }

    /**
     * Setup the forum IDs that can be used when searching for the MySpot post submodules
     *
     * @param string $search_id The Search Content Enum
     * @param array  $forumIdExclusions
     * @param string $m_approve_topics_fid_sql
     */
    private function processMySpotSearchSetup($search_id, array &$forumIdExclusions = [], &$m_approve_topics_fid_sql = '')
    {

        $forumIdExclusions = array_unique(array_merge(array_keys($this->auth->acl_getf('!f_read', true)), array_keys($this->auth->acl_getf('!f_search', true))));
        $cursor = query::getMySpotPostSearchResultCursor($this, $this->user->session_id, $forumIdExclusions, $this->user->data['user_id']);

        while ($forumRow = $this->db->sql_fetchrow($cursor)) {
            if ($forumRow['forum_password'] && $forumRow['user_id'] != $this->user->data['user_id']) {
                // User doesn't have access to this forum...
                $forumIdExclusions[] = $forumRow['forum_id'];
            }

            // Exclude forums from active topics
            if (!($forumRow['forum_flags'] & FORUM_FLAG_ACTIVE_TOPICS) && ($search_id == 'active_topics')) {
                $ex_fid_ary[] = (int)$forumRow['forum_id'];
                continue;
            }
        }
        query::freeCursor($this, $cursor);

        $m_approve_topics_fid_sql = $this->contentVisibility->get_global_visibility_sql('topic', $forumIdExclusions, 't.');
    }

    /**
     * Run the views & comments through the language processor consistently
     *
     * @param int $views
     * @param int $comments
     *
     * @return string
     */
    private function processTopicMeta($views, $comments)
    {
        return $this->language->lang('MYSPOT_TOPIC_VIEWS_COMMENTS', number_format((int)$views, 0), number_format((int)$comments, 0));
    }

    /**
     * Generate the 'Active Topics (90 days)' submodule
     */
    private function submoduleActiveTopics()
    {
        $forumIdExclusions = [];
        $approvedTopicForumIdsSql = '';

        $this->processMySpotSearchSetup('active_topics', $forumIdExclusions, $approvedTopicForumIdsSql);

        $sort_days = 90; // days; default: 7
        $cursor = query::getMySpotActiveTopicIdsCursor($this, $sort_days, $approvedTopicForumIdsSql, $forumIdExclusions);

        $field = 'topic_id';
        $id_ary = $this->postprocessMySpotSearchResults($cursor, $field);

        $this->prepareMySpotSearchResultsOutput('activetopics', $id_ary, $forumIdExclusions, $approvedTopicForumIdsSql);
    }

    /**
     * Generate the "What's New" submodule
     */
    private function submoduleNewPosts()
    {
        $forumIdExclusions = [];
        $approvedTopicForumIdsSql = '';

        $this->processMySpotSearchSetup('newposts', $forumIdExclusions, $approvedTopicForumIdsSql);

        // Set limit for the $total_match_count to reduce server load
        $total_matches_limit = 1000;
        // Only return up to $total_matches_limit+1 ids (the last one will be removed later)
        $cursor = query::getMySpotNewPostTopicIdsCursor($this, $this->user->data['user_lastvisit'], $approvedTopicForumIdsSql, $forumIdExclusions, $total_matches_limit + 1);

        $field = 'topic_id';
        $id_ary = $this->postprocessMySpotSearchResults($cursor, $field);

        $this->prepareMySpotSearchResultsOutput('newposts', $id_ary, $forumIdExclusions, $approvedTopicForumIdsSql);
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
     * Generate the Unanswered Topics submodule
     */
    private function submoduleUnansweredTopics()
    {
        $forumIdExclusions = [];
        $approvedTopicForumIdsSql = '';

        $this->processMySpotSearchSetup('unanswered', $forumIdExclusions, $approvedTopicForumIdsSql);

        $cursor = query::getMySpotUnansweredTopicIdsCursor($this, $approvedTopicForumIdsSql, $forumIdExclusions);

        $field = 'topic_id';
        $id_ary = $this->postprocessMySpotSearchResults($cursor, $field);

        $this->prepareMySpotSearchResultsOutput('unanswered', $id_ary, $forumIdExclusions, $approvedTopicForumIdsSql);
    }

    /**
     * Generate the 'Unread Posts' submodule
     */
    private function submoduleUnreadPosts()
    {
        $forumIdExclusions = [];
        $approvedTopicForumIdsSql = '';
        $total_matches_limit = 1000; // Set limit for the $total_match_count to reduce server load

        $this->processMySpotSearchSetup('unreadposts', $forumIdExclusions, $approvedTopicForumIdsSql);

        // This is not moved to own query because it's done in another method... might rebuild method locally.
        $sql_sort = 'ORDER BY t.topic_last_post_time DESC';
        $sql_where = 'AND t.topic_moved_id = 0
					AND ' . $approvedTopicForumIdsSql . '
					' . ((count($forumIdExclusions)) ? 'AND ' . $this->db->sql_in_set('t.forum_id', $forumIdExclusions, true) : '');

        // Only return up to $total_matches_limit+1 ids (the last one will be removed later)
        $id_ary = array_keys(get_unread_topics($this->user->data['user_id'], $sql_where, $sql_sort, $total_matches_limit + 1));

        $this->prepareMySpotSearchResultsOutput('unreadposts', $id_ary, $forumIdExclusions, $approvedTopicForumIdsSql);
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
                    'URL'          => $this->helper->route(url::ROUTE_MEMBER, ['id' => $userRow['user_id']]),
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

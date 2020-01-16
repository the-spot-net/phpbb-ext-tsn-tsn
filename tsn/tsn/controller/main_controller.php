<?php
/**
 * the-spot.net. An extension for the phpBB Forum Software package.
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license       MIT
 */

namespace tsn\tsn\controller;

use tsn\tsn\controller\traits\myspot;
use tsn\tsn\framework\constants\url;
use tsn\tsn\framework\logic\query;

/**
 * the-spot.net main controller.
 */
class main_controller extends AbstractBase
{
    use myspot;

    /**
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

        $this->moduleLogin();

        return $this->helper->render('@tsn_tsn/tsn_login.html', $this->language->lang('LOGIN'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response|void
     */
    private function moduleLogin()
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
     * Call the submodules for Statistics sidebar
     */
    private function moduleStatistics()
    {
        $this->submoduleUserBirthdays();
        $this->submoduleUsersOnline();
        $this->submoduleUserGroupLegend();
    }

    /**
     * Generates the Birthday List, if required.
     */
    private function submoduleUserBirthdays()
    {
        if ($this->getConfig('load_birthdays') && $this->getConfig('allow_birthdays') && $this->auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel')) {

            $time = $this->user->create_datetime();
            $cursor = query::getUserBirthdaysCursor($this->getDb(), $time);

            while ($userRow = $this->getDb()->sql_fetchrow($cursor)) {

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

            query::freeCursor($this->getDb(), $cursor);
        }
    }

    /**
     * Generate a Template Block Var array of User Group Legend Data
     */
    private function submoduleUserGroupLegend()
    {
        $cursor = query::getUserGroupLegendCursor($this->getDb(), $this->user->data['user_id'], $this->auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel'));

        while ($userGroupRow = $this->getDb()->sql_fetchrow($cursor)) {

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
        query::freeCursor($this->getDb(), $cursor);
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

<?php
/**
 * the-spot.net. An extension for the phpBB Forum Software package.
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license       MIT
 */

namespace tsn\tsn\event;

/**
 * @ignore
 */

use phpbb\auth\auth;
use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\template\template;
use phpbb\user;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use tsn\tsn\framework\constants\url;

/**
 * the-spot.net Event listener.
 */
class main_listener implements EventSubscriberInterface
{
    /** @var \phpbb\auth\auth */
    protected $auth;
    /* @var \phpbb\language\language */
    protected $language;
    /* @var \phpbb\controller\helper */
    protected $helper;
    /* @var \phpbb\template\template */
    protected $template;
    /** @var string phpEx */
    protected $php_ext;
    /* @var \phpbb\user */
    protected $user;

    /**
     * Constructor
     *
     * @param \phpbb\auth\auth         $auth
     * @param \phpbb\language\language $language Language object
     * @param \phpbb\controller\helper $helper   Controller helper object
     * @param \phpbb\template\template $template Template object
     * @param string                   $php_ext  phpEx
     */
    public function __construct(auth $auth, language $language, helper $helper, template $template, $php_ext, user $user)
    {
        $this->auth = $auth;
        $this->language = $language;
        $this->helper = $helper;
        $this->template = $template;
        $this->php_ext = $php_ext;
        $this->user = $user;
    }

    /**
     * @return array
     * @author phpbb
     */
    public static function getSubscribedEvents()
    {
        return [
            'core.permissions'                              => 'add_permissions',
            'core.user_setup'                               => 'load_language_on_setup',
            'core.get_avatar_after'                         => 'processUserAvatar',
            'core.obtain_users_online_string_sql'           => 'injectOnlineAvatarQuery',
            'core.obtain_users_online_string_before_modify' => 'processOnlineAvatarQuery',
            'core.modify_username_string'                   => 'convertProfileUrlToRoute',
            //            'core.page_header'                         => 'add_page_header_link',
            //            'core.viewonline_overwrite_location'       => 'viewonline_page',
            //            'core.display_forums_modify_template_vars' => 'display_forums_modify_template_vars',
        ];
    }

    /**
     * Add permissions to the ACP -> Permissions settings page
     * This is where permissions are assigned language keys and
     * categories (where they will appear in the Permissions table):
     * actions|content|forums|misc|permissions|pm|polls|post
     * post_actions|posting|profile|settings|topic_actions|user_group
     * Developers note: To control access to ACP, MCP and UCP modules, you
     * must assign your permissions in your module_info.php file. For example,
     * to allow only users with the a_new_tsn_tsn permission
     * access to your ACP module, you would set this in your acp/main_info.php:
     *    'auth' => 'ext_tsn/tsn && acl_a_new_tsn_tsn'
     *
     * @param \phpbb\event\data $event Event object
     */
    public function add_permissions($event)
    {
        $permissions = $event['permissions'];

        $permissions['a_new_tsn_tsn'] = ['lang' => 'ACL_A_NEW_TSN_TSN', 'cat' => 'misc'];
//        $permissions['m_new_tsn_tsn'] = ['lang' => 'ACL_M_NEW_TSN_TSN', 'cat' => 'post_actions'];
//        $permissions['u_new_tsn_tsn'] = ['lang' => 'ACL_U_NEW_TSN_TSN', 'cat' => 'post'];

        $event['permissions'] = $permissions;
    }

    /**
     * @note Available fields: 'mode', 'user_id', 'username', 'username_colour', 'guest_username', 'custom_profile_url', 'username_string', '_profile_cache',
     */
    public function convertProfileUrlToRoute($event)
    {

        $mode = $event['mode'];
        $userId = $event['user_id'];
        $userName = $event['username'];
        $userColor = $event['username_colour'];
        $profileCache = $event['_profile_cache'];

        // Cleanup User Color: maybe empty, maybe ##, maybe 3|6 hex chars
        $userColor = (strlen($userColor) > 2) ? '#' . ltrim($userColor, '#') : '';

        // Build the correct profile url, if not anonymous & viewer has permissions to view profiles
        // For anonymous, redirect to login page
        $profileUrl = '';

        if ($userId && $userId != ANONYMOUS && ($this->user->data['user_id'] == ANONYMOUS || $this->auth->acl_get('u_viewprofile'))) {
            $profileUrl = $this->helper->route(url::ROUTE_MEMBER, ['id' => (int)$userId]);
        }

        // Return profile
        if ($mode == 'profile') {
            $result = $profileUrl;
        } else if (!isset($result)) {
            if (($mode == 'full' && empty($profileUrl)) || $mode == 'no_profile') {
                $templateString = (!$userColor)
                    ? $profileCache['tpl_noprofile']
                    : $profileCache['tpl_noprofile_colour'];

                $result = str_replace(['{USERNAME_COLOUR}', '{USERNAME}'], [$userColor, $userName], $templateString);
            } else {
                $templateString = (!$userColor)
                    ? $profileCache['tpl_profile']
                    : $profileCache['tpl_profile_colour'];

                $result = str_replace(['{PROFILE_URL}', '{USERNAME_COLOUR}', '{USERNAME}'], [$profileUrl, $userColor, $userName], $templateString);
            }
        }

        $event['username_colour'] = $userColor;
        $event['username_string'] = $result;
    }

    /**
     * Online Avatar List: Part 1
     * Inject the query select fields
     * @note Available Fields: 'online_users', 'item_id', 'item', 'sql_ary'
     *
     * @param $event
     *
     * @see  \obtain_users_online_string()
     */
    public function injectOnlineAvatarQuery($event)
    {
        $sqlArray = $event['sql_ary'];

        $sqlArray['SELECT'] = implode(', ', array_unique(array_filter(array_merge(
            explode(', ', $sqlArray['SELECT']),
            // Inject the necessary fields
            ['u.user_avatar', 'u.user_avatar_type', 'u.user_avatar_width', 'u.user_avatar_height']
        ))));

        $event['sql_ary'] = $sqlArray;
    }

    /**
     * Load common language files during user setup
     *
     * @param \phpbb\event\data $event Event object
     */
    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'tsn/tsn',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    /**
     * Online Avatar List: Part 2
     * Process the `rowset` to generate Online Avatar List Images
     * @note Available Fields: 'online_users', 'item_id', 'item', 'rowset', 'user_online_link'
     *
     * @param $event
     *
     * @see  \obtain_users_online_string()
     */
    public function processOnlineAvatarQuery($event)
    {
        $rowset = $event['rowset'];
        $online_users = $event['online_users'];

        foreach ($rowset as $row) {

            if ($row['user_id'] != ANONYMOUS) {

                if (!isset($online_users['hidden_users'][$row['user_id']])
                    || $this->auth->acl_get('u_viewonline')
                    || $row['user_id'] === $this->user->data['user_id']
                ) {

                    // Calculate the results of scaling...
                    $scale = 0.2;
                    $temp_scaled_width = (float)$row['user_avatar_width'] * $scale;
                    $temp_scaled_height = (float)$row['user_avatar_height'] * $scale;

                    // Avatars are assumed to be 100px by 100px
                    $control_scaled_side = (float)100 * $scale;

                    if ($temp_scaled_height && $temp_scaled_width) {

                        // Will scaling it cause one side to be bigger than the control?
                        $isScaledTooBig = ($temp_scaled_height > $control_scaled_side || $temp_scaled_width > $control_scaled_side);
                        // Will scaling it cause both sides to be smaller than the control?
                        $isScaledTooSmall = ($temp_scaled_height < $control_scaled_side && $temp_scaled_width < $control_scaled_side);

                        // The scaled dimensions are insufficient and need to be further scaled to a control...
                        if ($isScaledTooBig || $isScaledTooSmall) {
                            // If the width is largest, max it at the control width,
                            // and scale the height to match...
                            if ($temp_scaled_width >= $temp_scaled_height) {
                                $scaled_width = $control_scaled_side;
                                $scaled_height = ($temp_scaled_height * $control_scaled_side) / $temp_scaled_width;
                            } else {
                                // Height is largest, scale on the width to match
                                $scaled_height = $control_scaled_side;
                                $scaled_width = ($temp_scaled_width * $control_scaled_side) / $temp_scaled_height;
                            }
                        } else {
                            // Scaling resulted in sufficient dimensions, use them
                            $scaled_width = $temp_scaled_width;
                            $scaled_height = $temp_scaled_height;
                        }

                        $row['user_avatar_width'] = $scaled_width;
                        $row['user_avatar_height'] = $scaled_height;
                    } else {
                        $row['user_avatar_width'] = $control_scaled_side;
                        $row['user_avatar_height'] = $control_scaled_side;
                    }
                    $row['avatar_title'] = $row['username'];

                    $this->template->assign_block_vars('online_avatars', [
                        'I_AVATAR_IMG' => phpbb_get_user_avatar($row, 'USER_AVATAR', false, false),
                    ]);
                }
            }
        }

        $event['rowset'] = $rowset;
        $event['online_users'] = $online_users;
    }

//    /**
//     * Add a link to the controller in the forum navbar
//     */
//    public function add_page_header_link()
//    {
//        $this->template->assign_vars([
//            'U_TSN_PAGE' => $this->helper->route('tsn_tsn_index', ['name' => 'world']),
//        ]);
//    }

//    /**
//     * A sample PHP event
//     * Modifies the names of the forums on index
//     *
//     * @param \phpbb\event\data $event Event object
//     */
//    public function display_forums_modify_template_vars($event)
//    {
//        $forum_row = $event['forum_row'];
//        $forum_row['FORUM_NAME'] .= $this->language->lang('TSN_EVENT');
//        $event['forum_row'] = $forum_row;
//    }

    /**
     * @param $event
     *
     * @throws \Exception
     * @used-by \phpbb_get_avatar()
     * @author  neotsn
     */
    public function processUserAvatar($event)
    {
        /*
         * Available Fields: 'row', 'alt', 'ignore_config', 'avatar_data', 'html'
         */
        global $phpbb_container, $language;

        $row = $event['row'];
        $alt = $event['alt'];
        $avatar_data = $event['avatar_data'];
        $ignore_config = $event['ignore_config'];

        $imgArray = [];
        // check for remote file...
        if ($row['avatar_type'] == 'avatar.driver.remote') {
            // Test for image existence
            $imgArray = @getimagesize($avatar_data['src']);
        }

        // If remote doesn't exist, or no avatar for user, get default
        if (($row['avatar_type'] == 'avatar.driver.remote' && empty($imgArray[0])) || empty($avatar_data['src'])) {
            // Set default image info; TODO Put this info in the database via extension
            $row['avatar_type'] = 'avatar.driver.local';
            $row['avatar'] = 'novelties/tsn_icon_avatar.png';
            $row['avatar_width'] = $row['avatar_width'] ?: 100;
            $row['avatar_height'] = $row['avatar_height'] ?: 100;

            // Run through the proper channels again with local file...
            /* @var $phpbb_avatar_manager \phpbb\avatar\manager */
            $phpbb_avatar_manager = $phpbb_container->get('avatar.manager');

            if ($driver = $phpbb_avatar_manager->get_driver($row['avatar_type'], $ignore_config)) {
                $avatar_data = $driver->get_data($row, $ignore_config);
            } else {
                $avatar_data['src'] = '';
            }

            // Set all dimensions to the largest side;
            // if via tsn8 extension it will have been scaled/resized to the max for the feature
            // if otherwise, it will be default image size - for avatars that is 100x100
            $avatar_data['width'] = $avatar_data['height'] = ($row['avatar_width'] >= $row['avatar_height']) ? $row['avatar_width'] : $row['avatar_height'];
        }

        // Set the title text...
        $avatar_data['title'] = (!empty($row['avatar_title'])) ? $row['avatar_title'] : '';

        $html = '<img class="avatar" src="' . $avatar_data['src'] . '" ' .
            ($avatar_data['width'] ? ('width="' . $avatar_data['width'] . '" ') : '') .
            ($avatar_data['height'] ? ('height="' . $avatar_data['height'] . '" ') : '') .
            'title="' . (!empty($avatar_data['title']) ? $avatar_data['title'] : '') . '" ' .
            'alt="' . ($language->lang($alt) ?: $alt) . '" />';

        $event['row'] = $row;
        $event['alt'] = $alt;
        $event['avatar_data'] = $avatar_data;
        $event['ignore_config'] = $ignore_config;
        $event['html'] = $html;
    }

//    /**
//     * Show users viewing the-spot.net page on the Who Is Online page
//     *
//     * @param \phpbb\event\data $event Event object
//     */
//    public function viewonline_page($event)
//    {
//        if ($event['on_page'][1] === 'app' && strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/demo') === 0) {
//            $event['location'] = $this->language->lang('VIEWING_TSN_TSN');
//            $event['location_url'] = $this->helper->route('tsn_tsn_index', ['name' => 'world']);
//        }
//    }
}

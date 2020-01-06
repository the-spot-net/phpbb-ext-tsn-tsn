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

use phpbb\controller\helper;
use phpbb\language\language;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * the-spot.net Event listener.
 */
class main_listener implements EventSubscriberInterface
{
    /* @var \phpbb\language\language */
    protected $language;
    /* @var \phpbb\controller\helper */
    protected $helper;
    /* @var \phpbb\template\template */
    protected $template;
    /** @var string phpEx */
    protected $php_ext;

    /**
     * Constructor
     *
     * @param \phpbb\language\language $language Language object
     * @param \phpbb\controller\helper $helper   Controller helper object
     * @param \phpbb\template\template $template Template object
     * @param string                   $php_ext  phpEx
     */
    public function __construct(language $language, helper $helper, template $template, $php_ext)
    {
        $this->language = $language;
        $this->helper = $helper;
        $this->template = $template;
        $this->php_ext = $php_ext;
    }

    /**
     * @return array
     * @author phpbb
     */
    public static function getSubscribedEvents()
    {
        return [
            'core.user_setup'       => 'load_language_on_setup',
            'core.get_avatar_after' => 'processUserAvatar',
            //            'core.page_header'                         => 'add_page_header_link',
            //            'core.viewonline_overwrite_location'       => 'viewonline_page',
            //            'core.display_forums_modify_template_vars' => 'display_forums_modify_template_vars',
            'core.permissions'      => 'add_permissions',
        ];
    }

    /**
     * @param $event
     *
     * @throws \Exception
     * @used-by \phpbb_get_avatar()
     * @author  neotsn
     */
    public static function processUserAvatar($event)
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
//     * Add a link to the controller in the forum navbar
//     */
//    public function add_page_header_link()
//    {
//        $this->template->assign_vars([
//            'U_TSN_PAGE' => $this->helper->route('tsn_tsn_controller', ['name' => 'world']),
//        ]);
//    }

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

//    /**
//     * Show users viewing the-spot.net page on the Who Is Online page
//     *
//     * @param \phpbb\event\data $event Event object
//     */
//    public function viewonline_page($event)
//    {
//        if ($event['on_page'][1] === 'app' && strrpos($event['row']['session_page'], 'app.' . $this->php_ext . '/demo') === 0) {
//            $event['location'] = $this->language->lang('VIEWING_TSN_TSN');
//            $event['location_url'] = $this->helper->route('tsn_tsn_controller', ['name' => 'world']);
//        }
//    }
}

<?php
/**
 * the-spot.net. An extension for the phpBB Forum Software package.
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license       MIT
 */

if (!defined('IN_PHPBB')) {
    exit;
}

if (empty($lang) || !is_array($lang)) {
    $lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, [

    // From Sample Project
    'TSN_HELLO'             => 'Hello %s!',
    'TSN_GOODBYE'           => 'Goodbye %s!',
    'TSN_EVENT'             => ' :: Tsn Event :: ',
    'ACP_TSN_GOODBYE'       => 'Should say goodbye?',
    'TSN_PAGE'              => 'Tsn Page',
    'TSN_TSN_NOTIFICATION'  => 'the-spot.net notification',
    'VIEWING_TSN_TSN'       => 'Viewing the-spot.net page',

    // Used Items: General
    'REGISTER'              => 'Register',
    'SIGN_IN'               => 'Sign In',
    'WELCOME_VISITOR'       => 'Welcome to tsn!',
    'JOIN_THE_DISCUSSION'   => 'Join the discussion...',
    'TSN_VERSION'           => 'tsn v9.0.0.328',
    'TSN_VERSION_LINK'      => 'https://github.com/the-spot-net/tsn8/releases/latest',
    'COPYRIGHT_CREDITS'     => '&copy; 2003 - ' . date('Y', time()) . ' <a href="https://the-spot.net" target="_blank">the-spot.net</a>. | Developed by <a href="https://thepizzy.net/blog" target="_blank">@neotsn</a> | Powered by <a href="https://www.phpbb.com" target="_blank">these guys</a>',

    // Used Items: My Spot
    'MYSPOT'                => 'My Spot',
    'MYSPOT_LEGEND'         => 'User Role Legend',
    'MYSPOT_NEWEST_USER'    => 'Newest User',
    'MYSPOT_RECORD'         => 'Online Users Record',
    'MYSPOT_SPECIAL_REPORT' => '#tsnSpecialReport',
    'ON'                    => 'on',
    'ONLINE_NOW'            => 'Online Now',

    // Need Verification: General
    'ABOUT'                 => 'About',
    'ALLOW_VOTE_CHANGE'     => 'Allow users to change their vote',
    'BBCODE'                => 'BBCode',
    'BY'                    => 'By',
    'CLICK_TO_CONFIRM'      => 'Click to Confirm',
    'CONTINUE_READING'      => 'Continue Reading',

    'DISABLE'                             => 'Disable',
    'ENABLE'                              => 'Enable',
    'GROUP_LEADERS'                       => 'Group Leaders',
    //    'USERS_ONLINE'                        => 'Online',
    //    'VISIBLE'                             => 'Visible',
    //    'GUESTS'                              => 'Guests',
    //    'HAPPY_BIRTHDAY_TO'                   => 'Happy Birthday to:',
    //    'HIDDEN'                              => 'Hidden',
    //    'MYSPOT_RECORD_USERS'                 => 'Record Users',
    //    'MYSPOT_RECORD_ONLINE_USERS'          => '<strong>%1$s</strong> Users on<br />%2$s',
    'NOTHING_SINCE_LAST_VISIT'            => 'You are all caught up!',
    'PERCENT_OF_TOTAL'                    => '% of Total',
    'POSTED_BY'                           => 'Posted by',
    'POSTS_PER_DAY'                       => 'Posts/Day',
    'REPLY_NOTIFICATIONS'                 => 'Notify of Replies',
    'SIGNATURE'                           => 'Signature',
    'SINCE_YOUR_LAST_VISIT'               => 'Since you were last here...',
    'SMILIES'                             => 'Emojis',
    'SPECIAL_REPORT_VIEWS_COMMENTS_COUNT' => 'This post has been viewed %1$s times with %2$s comments',
    'START_THE_CONVERSATION'              => 'How about starting a new discussion in the forums?',
    'TSNBLOG'                             => 'tsnBlog',
    'TSNFORUMS'                           => 'tsnForums',
    'URLS'                                => 'URLs',
    'VIEW_PROFILE'                        => 'View Profile',
    'VISITED'                             => 'Last Visited',

]);

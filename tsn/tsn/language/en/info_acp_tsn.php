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
    'ACP_TSN_TITLE' => 'the-spot.net Module',
    'ACP_TSN'       => 'the-spot.net Settings',

    'LOG_ACP_TSN_SETTINGS'     => '<strong>the-spot.net settings updated</strong>',

    // Updated Items: ACP
    'ACP_TSN_PLUGIN_SETTINGS'  => 'tsn Feature Settings',
    'ACP_TSN_ENABLE_EXTENSION' => 'Enable tsn Features',

    'ACP_TSN_MYSPOT_SETTINGS'     => '"My Spot" Module Settings',
    'ACP_TSN_ENABLE_MYSPOT'       => 'Display "My Spot" Module',
    'ACP_TSN_ENABLE_NEW_POSTS'    => 'Display New Posts Module',
    'ACP_TSN_ENABLE_MINI_FORUMS'  => 'Display Mini Forum Index Module',
    'ACP_TSN_ENABLE_MINI_PROFILE' => 'Display Mini Profile Module',

    'ACP_TSN_MYSPOT_SPECIAL_REPORT_SETTINGS' => '"My Spot :: Special Report" Module Settings',
    'ACP_TSN_ENABLE_SPECIAL_REPORT'          => 'Display Special Report Module',
    'ACP_TSN_SPECIAL_REPORT_FORUM_ID'        => 'Forum ID to use in "Special Report" feature',
    'ACP_TSN_SPECIAL_REPORT_EXCERPT_WORDS'   => 'Word Limit for "Special Report" excerpt',

    'ACP_TSN_SETTING_SAVED' => 'Settings have been saved successfully!',
]);

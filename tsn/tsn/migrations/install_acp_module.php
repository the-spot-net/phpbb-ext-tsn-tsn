<?php
/**
 * the-spot.net. An extension for the phpBB Forum Software package.
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license       MIT
 */

namespace tsn\tsn\migrations;

use phpbb\db\migration\migration;

/**
 * Class install_acp_module
 * @package tsn\tsn\migrations
 */
class install_acp_module extends migration
{
    /**
     * @return array
     */
    public static function depends_on()
    {
        return ['\phpbb\db\migration\data\v320\v320'];
    }

    /**
     * @return bool
     */
    public function effectively_installed()
    {
        return isset($this->config['tsn_enable_extension']);
    }

    /**
     * @return array
     */
    public function update_data()
    {
        return [
            ['config.add', ['tsn_enable_extension', 1]],
            ['config.add', ['tsn_enable_newposts', 1]],
            ['config.add', ['tsn_enable_myspot', 1]],
            ['config.add', ['tsn_enable_miniforums', 1]],
            ['config.add', ['tsn_enable_miniprofile', 1]],
            ['config.add', ['tsn_enable_specialreport', 1]],
            ['config.add', ['tsn_specialreport_forumid', 1]],
            ['config.add', ['tsn_specialreport_excerpt_words', 140]],

            ['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_TSN_TITLE']],
            ['module.add', ['acp', 'ACP_TSN_TITLE', ['module_basename' => '\tsn\tsn\acp\main_module', 'modes' => ['settings']]]],
        ];
    }
}

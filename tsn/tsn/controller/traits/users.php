<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/16/20
 * Time: 9:24 AM
 */

namespace tsn\tsn\controller\traits;

use phpbb\db\driver\factory;
use tsn\tsn\framework\logic\query;

/**
 * Trait users
 * Handle user-related methods & replacements for phpbb functions
 * @package tsn\tsn\controller\traits
 * @method factory getDb()
 * @property \phpbb\auth\auth         $auth
 * @property \phpbb\controller\helper $helper
 * @property \phpbb\language\language $language
 * @property \phpbb\user              $user
 */
trait users
{
    /**
     * Run the submodule work for a User Avatar
     *
     * @param int $userId
     *
     * @return string
     */
    protected function generateUserAvatar(int $userId)
    {
        $avatarImage = '';
        if ($avatarRow = query::getUserAvatar($this->getDb(), $userId)) {

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
}

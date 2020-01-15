<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/3/20
 * Time: 1:54 PM
 */

namespace tsn\tsn\framework\logic;

use DateTime;
use phpbb\db\driver\factory;

/**
 * Class query
 * Handles running specific queries
 * @package tsn\tsn\controller
 */
class query
{
    // SQL Query Replacement Tokens
    const TOKEN_DATE = '{DATE}';
    const TOKEN_FORUM_ID = '{FORUM_ID}';
    const TOKEN_FORUM_ID_EXCLUSIONS = '{FORUM_ID_EXCLUSIONS}';
    const TOKEN_FORUM_ID_WHITELIST = '{FORUM_ID_WHITELIST}';
    const TOKEN_FORUM_IDS = '{FORUM_IDS}';
    const TOKEN_LEAP_DATE = '{LEAP_DATE}';
    const TOKEN_SESSION_ID = '{SESSION_ID}';
    const TOKEN_TOPIC_ID = '{TOPIC_ID}';
    const TOKEN_TOPIC_IDS = '{TOPIC_IDS}';
    const TOKEN_USER_ID = '{USER_ID}';

    // SQL Queries
    const SQL_SPECIAL_REPORT_NEWEST_TOPIC_ID = 'SELECT MAX(topic_id) AS topic_id FROM ' . TOPICS_TABLE . ' WHERE forum_id = ' . self::TOKEN_FORUM_ID;
    const SQL_SPECIAL_REPORT_NEWEST_TOPIC_DETAILS = 'SELECT f.forum_name, t.forum_id, t.topic_id, t.topic_title, t.topic_views, t.topic_posts_approved, t.topic_time, t.topic_poster, p.enable_smilies, p.post_id, p.post_text, p.bbcode_uid, p.bbcode_bitfield, u.username, u.user_colour FROM ' . TOPICS_TABLE . ' t LEFT JOIN ' . POSTS_TABLE . ' p ON (t.topic_id = p.topic_id AND t.topic_first_post_id = p.post_id) LEFT JOIN ' . USERS_TABLE . ' u ON (t.topic_poster = u.user_id) LEFT JOIN ' . FORUMS_TABLE . ' f ON (t.forum_id = f.forum_id) WHERE t.forum_id = ' . self::TOKEN_FORUM_ID . ' AND t.topic_id = ' . self::TOKEN_TOPIC_ID;
    const SQL_MYSPOT_ACTIVE_TOPICS_INFOS = 'SELECT t.topic_last_post_time, t.topic_id FROM ' . TOPICS_TABLE . ' t WHERE t.topic_moved_id = 0 ' . self::TOKEN_DATE . ' AND ' . self::TOKEN_FORUM_ID_WHITELIST . ' ' . self::TOKEN_FORUM_ID_EXCLUSIONS . ' ORDER BY t.topic_last_post_time DESC';
    const SQL_MYSPOT_NEW_POSTS_FORUM_INFO = 'SELECT f.forum_id, f.forum_name, f.parent_id, f.forum_type, f.right_id, f.forum_password, f.forum_flags, fa.user_id FROM ' . FORUMS_TABLE . ' f  LEFT JOIN ' . FORUMS_ACCESS_TABLE . ' fa ON (fa.forum_id = f.forum_id AND fa.session_id = "' . self::TOKEN_SESSION_ID . '") ' . self::TOKEN_FORUM_ID_EXCLUSIONS . ' ORDER BY f.left_id';
    const SQL_MYSPOT_NEW_POSTS_TOPIC_IDS = 'SELECT t.topic_id FROM ' . TOPICS_TABLE . ' t WHERE t.topic_last_post_time > ' . self::TOKEN_DATE . ' AND t.topic_moved_id = 0 AND ' . self::TOKEN_FORUM_ID_WHITELIST . ' ' . self::TOKEN_FORUM_ID_EXCLUSIONS . ' ORDER BY t.topic_last_post_time DESC';
    const SQL_MYSPOT_NEW_POSTS_TOPIC_INFOS = 'SELECT t.*, f.forum_id, f.forum_name, tt.mark_time, ft.mark_time as f_mark_time, p.post_text, p.bbcode_uid, p.bbcode_bitfield FROM ' . TOPICS_TABLE . ' t  LEFT JOIN ' . POSTS_TABLE . ' p ON (t.topic_last_post_id = p.post_id) LEFT JOIN ' . FORUMS_TABLE . ' f ON (f.forum_id = t.forum_id) LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.user_id = ' . self::TOKEN_USER_ID . ' AND t.topic_id = tt.topic_id) LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . self::TOKEN_USER_ID . ' AND ft.forum_id = f.forum_id)' . ' WHERE ' . self::TOKEN_TOPIC_IDS . ' ' . self::TOKEN_FORUM_ID_EXCLUSIONS . ' AND ' . self::TOKEN_FORUM_ID_WHITELIST . ' ORDER BY t.topic_last_post_time DESC';
    const SQL_MYSPOT_SEARCH_TOPIC_INFOS = 'SELECT t.*, p.post_text, p.bbcode_uid, p.bbcode_bitfield FROM ' . TOPICS_TABLE . ' t LEFT JOIN ' . POSTS_TABLE . ' p ON (t.topic_last_post_id = p.post_id) WHERE ' . self::TOKEN_TOPIC_IDS;
    const SQL_MYSPOT_UNANSWERED_TOPIC_INFOS = 'SELECT DISTINCT t.topic_last_post_time, p.topic_id FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t WHERE t.topic_posts_approved = 1 AND t.topic_moved_id = 0 AND p.topic_id = t.topic_id AND ' . self::TOKEN_FORUM_ID_WHITELIST . ' ' . self::TOKEN_FORUM_ID_EXCLUSIONS . ' ORDER BY t.topic_last_post_time DESC';
    const SQL_TOPIC_UNREAD_STATUS = 'SELECT t.*, f.forum_id, f.forum_name, tp.topic_posted, tt.mark_time, ft.mark_time AS f_mark_time, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, p.post_text, p.bbcode_uid, p.bbcode_bitfield FROM ' . POSTS_TABLE . ' p, ' . TOPICS_TABLE . ' t LEFT JOIN ' . FORUMS_TABLE . ' f ON (f.forum_id = t.forum_id) LEFT JOIN ' . TOPICS_POSTED_TABLE . ' tp ON (tp.user_id = ' . self::TOKEN_USER_ID . ' AND t.topic_id = tp.topic_id) LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.user_id = ' . self::TOKEN_USER_ID . ' AND t.topic_id = tt.topic_id) LEFT JOIN ' . FORUMS_TRACK_TABLE . ' ft ON (ft.user_id = ' . self::TOKEN_USER_ID . ' AND ft.forum_id = f.forum_id) LEFT JOIN ' . USERS_TABLE . ' u ON (u.user_id = t.topic_last_poster_id) WHERE t.topic_id = ' . self::TOKEN_TOPIC_ID . ' AND f.forum_id = ' . self::TOKEN_FORUM_ID . ' AND t.forum_id = ' . self::TOKEN_FORUM_ID . ' AND t.topic_visibility = 1 AND p.post_id = t.topic_last_post_id ORDER BY t.topic_last_post_time DESC';
    const SQL_USER_AVATAR = 'SELECT u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height FROM ' . USERS_TABLE . ' u WHERE u.user_id = ' . self::TOKEN_USER_ID;
    const SQL_USER_BIRTHDAYS = 'SELECT u.user_id, u.username, u.user_colour, u.user_birthday FROM ' . USERS_TABLE . ' u LEFT JOIN ' . BANLIST_TABLE . ' b ON (u.user_id = b.ban_userid) WHERE (b.ban_id IS NULL OR b.ban_exclude = 1) AND (u.user_birthday LIKE "' . self::TOKEN_DATE . '%" ' . self::TOKEN_LEAP_DATE . ') AND u.user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')';
    const SQL_USER_GROUPS_LEGEND_ALL = 'SELECT group_id, group_name, group_colour, group_type, group_legend FROM ' . GROUPS_TABLE . ' WHERE group_legend > 0 ORDER BY group_legend';
    const SQL_USER_GROUPS_LEGEND_RESTRICTED = 'SELECT g.group_id, g.group_name, g.group_colour, g.group_type, g.group_legend FROM ' . GROUPS_TABLE . ' g LEFT JOIN ' . USER_GROUP_TABLE . ' ug ON (g.group_id = ug.group_id AND ug.user_id = ' . self::TOKEN_USER_ID . ' AND ug.user_pending = 0) WHERE g.group_legend > 0 AND (g.group_type <> ' . GROUP_HIDDEN . ' OR ug.user_id = ' . self::TOKEN_USER_ID . ') ORDER BY g.group_legend';
    const SQL_USER_SESSION_TIME = 'SELECT MAX(session_time) AS session_time, MIN(session_viewonline) AS session_viewonline FROM ' . SESSIONS_TABLE . ' WHERE session_user_id = ' . self::TOKEN_USER_ID;

    // SQL Query Injection phrases
    const SQL_INJECT_USER_LEAP_BIRTHDAYS = ' OR u.user_birthday LIKE "' . self::TOKEN_DATE . '%"';
    const SQL_INJECT_MYSPOT_POST_SEARCH_FORUM_INFO_FORUM_EXCLUSIONS = ' WHERE ' . self::TOKEN_FORUM_IDS . ' OR (f.forum_password <> "" AND fa.user_id <> ' . self::TOKEN_USER_ID . ')';

    /**
     * Return the minimal data set for the latest topic from the Special Report Forum
     *
     * @param \phpbb\db\driver\factory $db
     * @param int                      $forumId
     *
     * @return mixed
     */
    public static function checkForSpecialReportLatestTopic(factory $db, int $forumId)
    {
        $query = str_replace(self::TOKEN_FORUM_ID, $forumId, self::SQL_SPECIAL_REPORT_NEWEST_TOPIC_ID);

        return self::executeQuery($db, $query);
    }

    /**
     * Release the DB Cursor
     *
     * @param \phpbb\db\driver\factory            $db
     * @param                                     $cursor
     */
    public static function freeCursor(factory $db, $cursor)
    {
        $db->sql_freeresult($cursor);
    }

    /**
     * Return the cursor for the Active Topics Module of MySpot
     *
     * @param \phpbb\db\driver\factory            $db
     * @param int                                 $sort_days
     * @param                                     $approvedTopicForumIdsSql
     * @param array                               $forumIdExclusions
     *
     * @return mixed
     */
    public static function getMySpotActiveTopicIdsCursor(factory $db, int $sort_days, $approvedTopicForumIdsSql, array $forumIdExclusions)
    {
        $last_post_time_sql = ($sort_days) ? ' AND t.topic_last_post_time > ' . (time() - ($sort_days * 24 * 3600)) : '';
        $forumIdExclusionSql = (count($forumIdExclusions)) ? ' AND ' . $db->sql_in_set('t.forum_id', $forumIdExclusions, true) : '';

        $query = str_replace(self::TOKEN_DATE, $last_post_time_sql, self::SQL_MYSPOT_ACTIVE_TOPICS_INFOS);
        $query = str_replace(self::TOKEN_FORUM_ID_WHITELIST, $approvedTopicForumIdsSql, $query);
        $query = str_replace(self::TOKEN_FORUM_ID_EXCLUSIONS, $forumIdExclusionSql, $query);

        return $db->sql_query($query);
    }

    /**
     * Pull the Topic IDs for which there are new posts for this user since their last visit
     *
     * @param \phpbb\db\driver\factory $db
     * @param int                      $userLastVisit
     * @param string                   $approvedTopicsVisibilitySql
     * @param int[]                    $forumIdExclusions
     * @param int                      $total_matches_limit
     *
     * @return bool|mixed
     */
    public static function getMySpotNewPostTopicIdsCursor(factory $db, int $userLastVisit, $approvedTopicsVisibilitySql, array $forumIdExclusions, $total_matches_limit = 1000)
    {
        $forumExclusionSql = (count($forumIdExclusions))
            ? 'AND ' . $db->sql_in_set('t.forum_id', $forumIdExclusions, true)
            : '';

        $query = str_replace(self::TOKEN_DATE, $userLastVisit, self::SQL_MYSPOT_NEW_POSTS_TOPIC_IDS);
        $query = str_replace(self::TOKEN_FORUM_ID_WHITELIST, $approvedTopicsVisibilitySql, $query);
        $query = str_replace(self::TOKEN_FORUM_ID_EXCLUSIONS, $forumExclusionSql, $query);

        return $db->sql_query_limit($query, $total_matches_limit);
    }

    /**
     * @param \phpbb\db\driver\factory            $db
     * @param int                                 $userId
     * @param array                               $topicIds
     * @param array                               $forumIdExclusions
     * @param                                     $approvedTopicsVisibilitySql
     *
     * @return mixed
     */
    public static function getMySpotNewPostsTopicDetailsCursor(factory $db, int $userId, array $topicIds, array $forumIdExclusions, $approvedTopicsVisibilitySql)
    {
        $forumIdExclusionSql = (count($forumIdExclusions)) ? ' AND (' . $db->sql_in_set('f.forum_id', $forumIdExclusions, true) . ' OR f.forum_id IS NULL)' : '';

        $query = str_replace(self::TOKEN_USER_ID, $userId, self::SQL_MYSPOT_NEW_POSTS_TOPIC_INFOS);
        $query = str_replace(self::TOKEN_TOPIC_IDS, $db->sql_in_set('t.topic_id', $topicIds), $query);
        $query = str_replace(self::TOKEN_FORUM_ID_EXCLUSIONS, $forumIdExclusionSql, $query);
        $query = str_replace(self::TOKEN_FORUM_ID_WHITELIST, $approvedTopicsVisibilitySql, $query);

        return $db->sql_query($query);
    }

    /**
     * @param \phpbb\db\driver\factory            $db
     * @param                                     $sessionId
     * @param                                     $forumIdExclusions
     * @param                                     $userId
     *
     * @return mixed
     */
    public static function getMySpotPostSearchResultCursor(factory $db, $sessionId, $forumIdExclusions, $userId)
    {
        $forumIdExclusionSql = (count($forumIdExclusions))
            ? $db->sql_in_set('f.forum_id', $forumIdExclusions, true)
            : "";

        if ($forumIdExclusionSql) {
            $queryInjection = str_replace(self::TOKEN_FORUM_IDS, $forumIdExclusionSql, self::SQL_INJECT_MYSPOT_POST_SEARCH_FORUM_INFO_FORUM_EXCLUSIONS);
            $queryInjection = str_replace(self::TOKEN_USER_ID, (int)$userId, $queryInjection);
        } else {
            $queryInjection = '';
        }

        $query = str_replace(self::TOKEN_SESSION_ID, $db->sql_escape($sessionId), self::SQL_MYSPOT_NEW_POSTS_FORUM_INFO);
        $query = str_replace(self::TOKEN_FORUM_ID_EXCLUSIONS, $queryInjection, $query);

        return $db->sql_query($query);
    }

    /**
     * Run the query for Unanswered Topic \s
     *
     * @param \phpbb\db\driver\factory            $db
     * @param                                     $approvedTopicForumIdsSql
     * @param                                     $forumIdExclusions
     *
     * @return mixed
     */
    public static function getMySpotUnansweredTopicIdsCursor(factory $db, $approvedTopicForumIdsSql, $forumIdExclusions)
    {
        $forumIdExclusionSql = ((count($forumIdExclusions))
            ? ' AND ' . $db->sql_in_set('p.forum_id', $forumIdExclusions, true)
            : '');

        $query = str_replace(self::TOKEN_FORUM_ID_WHITELIST, $approvedTopicForumIdsSql, self::SQL_MYSPOT_UNANSWERED_TOPIC_INFOS);
        $query = str_replace(self::TOKEN_FORUM_ID_EXCLUSIONS, $forumIdExclusionSql, $query);

        return $db->sql_query($query);
    }

    /**
     * Get the core data set for the Latest Topic in Special Report Forum; return it as an array
     *
     * @param \phpbb\db\driver\factory $db
     * @param int                      $forumId
     * @param int                      $topicId
     *
     * @return mixed
     */
    public static function getSpecialReportLatestTopicInfo(factory $db, int $forumId, int $topicId)
    {
        $query = str_replace(self::TOKEN_TOPIC_ID, $topicId, self::SQL_SPECIAL_REPORT_NEWEST_TOPIC_DETAILS);
        $query = str_replace(self::TOKEN_FORUM_ID, $forumId, $query);

        return self::executeQuery($db, $query);
    }

    /**
     * For the User, Topic, and Forum, get the read/unread status details
     *
     * @param \phpbb\db\driver\factory $db
     * @param int                      $userId
     * @param int                      $topicId
     * @param int                      $forumId
     *
     * @return mixed
     */
    public static function getTopicReadStatus(factory $db, int $userId, int $topicId, int $forumId)
    {
        // Get the current user's unread state for this topic...
        $query = str_replace(self::TOKEN_USER_ID, $userId, self::SQL_TOPIC_UNREAD_STATUS);
        $query = str_replace(self::TOKEN_TOPIC_ID, $topicId, $query);
        $query = str_replace(self::TOKEN_FORUM_ID, $forumId, $query);

        return self::executeQuery($db, $query);
    }

    /**
     * Get all info about the topicIds
     *
     * @param \phpbb\db\driver\factory            $db
     * @param                                     $topicIds
     *
     * @return mixed
     */
    public static function getTopicRowCursor(factory $db, $topicIds)
    {

        $query = str_replace(self::TOKEN_TOPIC_IDS, $db->sql_in_set('topic_id', array_keys($topicIds)), self::SQL_MYSPOT_SEARCH_TOPIC_INFOS);

        return $db->sql_query($query);
    }

    /**
     * Return the avatar row for a user id
     *
     * @param \phpbb\db\driver\factory $db
     * @param int                      $userId
     *
     * @return mixed
     */
    public static function getUserAvatar(factory $db, int $userId)
    {
        $query = str_replace(self::TOKEN_USER_ID, $userId, self::SQL_USER_AVATAR);

        return self::executeQuery($db, $query);
    }

    /**
     * @param \phpbb\db\driver\factory $db
     * @param \DateTime                $time
     *
     * @return mixed
     */
    public static function getUserBirthdaysCursor(factory $db, DateTime $time)
    {
        $now = phpbb_gmgetdate($time->getTimestamp() + $time->getOffset());

        $includeLeapYear = ($now['mday'] == 28 && $now['mon'] == 2 && !$time->format('L'));

        $query = str_replace(self::TOKEN_DATE, $db->sql_escape(sprintf('%2d-%2d-', $now['mday'], $now['mon'])), self::SQL_USER_BIRTHDAYS);
        // Conditionally inject the leap year date onto the query if needed.
        $query = str_replace(self::TOKEN_LEAP_DATE, ($includeLeapYear)
            ? str_replace(self::TOKEN_DATE, $db->sql_escape(sprintf('%2d-%2d-', 29, 2)), self::SQL_INJECT_USER_LEAP_BIRTHDAYS)
            : '', $query);

        return $db->sql_query($query);

    }

    /**
     * @param \phpbb\db\driver\factory $db
     * @param int                      $userId
     * @param bool                     $isRestricted
     *
     * @return mixed
     */
    public static function getUserGroupLegendCursor(factory $db, int $userId, bool $isRestricted = true)
    {
        $query = str_replace(self::TOKEN_USER_ID, $userId, ($isRestricted)
            ? self::SQL_USER_GROUPS_LEGEND_RESTRICTED
            : self::SQL_USER_GROUPS_LEGEND_ALL);

        return $db->sql_query($query);
    }

    /**
     * Get some basic Session details for the user for updating online time
     *
     * @param \phpbb\db\driver\factory $db
     * @param int                      $userId
     *
     * @return mixed
     */
    public static function getUserSessionTime(factory $db, int $userId)
    {
        $query = str_replace(self::TOKEN_USER_ID, $userId, self::SQL_USER_SESSION_TIME);

        return self::executeQuery($db, $query);
    }

    /**
     * Executes a query with a sql_fetchrow() call (single row return)
     *
     * @param \phpbb\db\driver\factory $db
     * @param string                   $query
     *
     * @return mixed
     */
    private static function executeQuery(factory $db, string $query)
    {
        $cursor = $db->sql_query($query);
        $result = $db->sql_fetchrow($cursor);
        self::freeCursor($db, $cursor);

        return $result;
    }
}

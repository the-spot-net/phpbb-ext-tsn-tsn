<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/15/20
 * Time: 9:26 AM
 */

namespace tsn\tsn\controller\traits;

use phpbb\config\config;
use phpbb\db\driver\factory;
use phpbb\user;
use tsn\tsn\controller\AbstractBase;
use tsn\tsn\framework\constants\url;
use tsn\tsn\framework\logic\query;

/**
 * Trait myspot
 * Handle the My Spot functionality for the controllers that need it
 * @package tsn\tsn\controller\traits
 * @method mixed|config getConfig($field = null)
 * @method factory getDb()
 * @method user getUser()
 * @method string processTopicMeta($viewCount, $replyCount)
 * @property \phpbb\auth\auth                  $auth
 * @property \phpbb\content_visibility         $contentVisibility
 * @property \phpbb\controller\helper          $helper
 * @property \phpbb\language\language          $language
 * @property \phpbb\request\request            $request
 * @property \tsn\tsn\framework\logic\template $template
 * @property \phpbb\user                       $user
 */
trait myspot
{
    private static $feedLimit = 25;

    /**
     * Generate the post sets for the MySpot page
     */
    private function moduleMySpotFeed()
    {
        $this->rootVars['S_ALLOW_SEARCH'] = ($this->auth->acl_get('u_search') || $this->auth->acl_getf_global('f_search') || $this->getConfig('load_search'));
        $this->rootVars['S_SEARCH_OVERLOADED'] = ($this->user->load
            && $this->getConfig('limit_search_load')
            && ($this->user->load > doubleval($this->getConfig('limit_search_load'))));

        $forumIdExclusions = [];

        $this->processMySpotFeedSetup('', $forumIdExclusions);

        $forumIdWhitelistSql = $this->contentVisibility->get_global_visibility_sql('topic', $forumIdExclusions, 't.');

        // Add 1 to the limit to see if there is another page
        $cursor = query::getMySpotFeedPage($this->getDb(), $forumIdWhitelistSql, $forumIdExclusions, (int)$this->user->data['user_id'], (int)$this->user->data['user_lastmark'], $this->request->variable('t', AbstractBase::$now), $this->request->variable('p', 1), self::$feedLimit + 1);

        $field = 'topic_id';

        $topicIds = [];
        while ($row = $this->getDb()->sql_fetchrow($cursor)) {
            $topicIds[] = (int)$row[$field];
        }
        query::freeCursor($this->getDb(), $cursor);

        $hasMore = false;
        if ($topicIds > self::$feedLimit) {
            $hasMore = true;
            $topicIds = array_slice($topicIds, 0, self::$feedLimit);
        }

        $this->prepareMySpotFeedOutput($topicIds, $forumIdExclusions, $forumIdWhitelistSql);

        return $hasMore;
    }

    /**
     * Handles the render of the Special Report AJAX request from tsn/special-report
     */
    private function moduleSpecialReport()
    {
        $this->blockVars['specialreport'] = [];

        // Get the minimal Latest Topic Info, if any
        if ($topicRow = query::checkForSpecialReportLatestTopic($this->getDb(), $this->getConfig('tsn_specialreport_forumid'))) {

            // Get the necessary topic info, since it exists

            if ($topicRow = query::getSpecialReportLatestTopicInfo($this->getDb(), $this->getConfig('tsn_specialreport_forumid'), $topicRow['topic_id'])) {

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

                $topicStatusRow = query::getTopicReadStatus($this->getDb(), $this->user->data['user_id'], $topicRow['topic_id'], $this->config['tsn_specialreport_forumid']);

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

                $this->blockVars['specialreport'][] = [
                    'FORUM_NAME' => $topicRow['forum_name'],

                    'TOPIC_ID'   => $topicRow['topic_id'],
                    'TOPIC_META' => $this->processTopicMeta($topicRow['topic_views'], (int)$topicRow['topic_posts_approved'] - 1),

                    'HEADLINE'           => censor_text($topicRow['topic_title']),
                    'I_AVATAR_IMG'       => $this->generateUserAvatar($topicRow['topic_poster']),
                    'POST_AUTHOR'        => get_username_string('full', $topicRow['topic_poster'], $topicRow['username'], $topicRow['user_colour']),
                    'POST_EXCERPT'       => implode(' ', array_slice($postWords, 0, 200)),
                    'POST_BODY'          => $postBody,
                    'POST_DATE'          => $this->user->format_date($topicRow['topic_time']),
                    'S_UNREAD_TOPIC'     => $isUnreadTopic,
                    'U_CONTINUE_READING' => $this->helper->route(url::ROUTE_POST, ['id' => $topicRow['post_id']]),
                    'U_FORUM'            => $this->helper->route(url::ROUTE_FORUM, ['id' => $topicRow['forum_id']]),
                ];
            }
        }
    }

    /**
     * @param $topicIds
     * @param $forumIdExclusions
     * @param $approvedTopicForumIdsSql
     */
    private function prepareMySpotFeedOutput($topicIds, $forumIdExclusions, $approvedTopicForumIdsSql)
    {
        $this->blockVars['topics'] = [];
        // make sure that some arrays are always in the same order
        sort($forumIdExclusions);

        $this->language->add_lang('viewtopic');

        if (count($topicIds)) {

            $topic_tracking_info = $forums = $rowset = $shadow_topic_list = [];

            $cursor = query::getMySpotFeedTopicsCursor($this->getDb(), $this->getUser()->data['user_id'], $topicIds, $forumIdExclusions, $approvedTopicForumIdsSql);

            while ($row = $this->getDb()->sql_fetchrow($cursor)) {

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
            query::freeCursor($this->getDb(), $cursor);

            // If we have some shadow topics, update the rowset to reflect their topic information
            if (count($shadow_topic_list)) {

                $cursor = query::getTopicRowCursor($this->getDb(), $shadow_topic_list);
                while ($row = $this->getDb()->sql_fetchrow($cursor)) {

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
                query::freeCursor($this->getDb(), $cursor);
            }
            $shadow_topic_list = null;

            foreach ($forums as $forumId => $forum) {
                if ($this->user->data['is_registered'] && $this->getConfig('load_db_lastread')) {
                    $topic_tracking_info[$forumId] = get_topic_tracking($forumId, $forum['topic_list'], $forum['rowset'], [$forumId => $forum['mark_time']]);
                }
            }
            $forums = null;

            if (!function_exists('topic_status')) {
                include_once('includes/functions_display.' . AbstractBase::$phpEx);
            }

            foreach ($rowset as $row) {
                $forumId = $row['forum_id'];
                $topicId = $row['topic_id'];
                $replies = $this->contentVisibility->get_count('topic_posts', $row, $forumId) - 1;

                $folder_img = $folder_alt = $topic_type = '';

                $unread_topic = (isset($topic_tracking_info[$forumId][$topicId]) && $row['topic_last_post_time'] > $topic_tracking_info[$forumId][$topicId]);
                topic_status($row, $replies, $unread_topic, $folder_img, $folder_alt, $topic_type);

                $firstPostBody = generate_text_for_display($row['first_post_text'], $row['first_post_bbcode_uid'], $row['first_post_bbcode_bitfield'], 1);
                $lastPostBody = generate_text_for_display($row['last_post_text'], $row['last_post_bbcode_uid'], $row['last_post_bbcode_bitfield'], 1);

                $this->blockVars['topics'][] = [
                    // Forum
                    'FORUM_NAME'                 => $row['forum_name'],

                    // Topic
                    'TOPIC_ID'                   => $topicId,
                    'S_UNREAD_TOPIC'             => $unread_topic,
                    'S_HAS_POLL'                 => (bool)$row['poll_start'],

                    // First Post
                    'FIRST_POST_ID'              => $row['topic_first_post_id'],
                    'FIRST_POST_AUTHOR_COLOR'    => get_username_string('no_profile', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']), // TODO
                    'FIRST_POST_SUBJECT'         => $row['topic_title'],
                    'FIRST_POST_TIME'            => $this->user->format_date($row['topic_time']),
                    // 'FIRST_POST_BODY'            => $firstPostBody,
                    'FIRST_POST_EXCERPT'         => implode(' ', array_slice(explode(' ', strip_tags($firstPostBody)), 0, 100)),
                    'I_FIRST_POST_AUTHOR_AVATAR' => $this->generateUserAvatar($row['topic_poster']),

                    // Meta
                    'VIEWS'                      => number_format((int)$row['topic_views'], 0),
                    'COMMENT_COUNT'              => number_format((int)$row['topic_posts_approved'] - 1, 0),
                    'NEW_COUNT'                  => '',

                    // Last Post
                    'LAST_POST_ID'               => $row['topic_last_post_id'],
                    'LAST_POST_AUTHOR_COLOR'     => get_username_string('no_profile', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
                    'LAST_POST_SUBJECT'          => $row['topic_last_post_subject'],
                    'LAST_POST_TIME'             => $this->user->format_date($row['topic_last_post_time']),
                    // 'LAST_POST_BODY'             => $lastPostBody,
                    'LAST_POST_EXCERPT'          => implode(' ', array_slice(explode(' ', strip_tags($lastPostBody)), 0, 100)),
                    'I_LAST_POST_AUTHOR_AVATAR'  => $this->generateUserAvatar($row['topic_last_poster_id']),

                    // 'U_MCP_REPORT' => $this->helper->route(url::ROUTE_MODERATOR, ['i' => 'reports', 'mode' => 'reports', 't' => $topicId], true, $this->user->session_id),
                    // 'U_VIEW_FORUM' => $this->helper->route(url::ROUTE_FORUM, ['id' => $forumId]),
                ];
            }
            $topic_tracking_info = null;
            $rowset = null;
        }
    }

    /**
     * Setup the forum IDs that can be used when searching for the MySpot post submodules
     *
     * @param string $search_id The Search Content Enum
     * @param array  $forumIdExclusions
     */
    private function processMySpotFeedSetup($search_id, array &$forumIdExclusions = [])
    {

        $forumIdExclusions = array_unique(array_merge(array_keys($this->auth->acl_getf('!f_read', true)), array_keys($this->auth->acl_getf('!f_search', true))));
        $cursor = query::getMySpotFeedForumsCursor($this->getDb(), $this->user->session_id, $forumIdExclusions, $this->user->data['user_id']);

        while ($forumRow = $this->getDb()->sql_fetchrow($cursor)) {
            if ($forumRow['forum_password'] && $forumRow['user_id'] != $this->user->data['user_id']) {
                // User doesn't have access to this forum...
                $forumIdExclusions[] = $forumRow['forum_id'];
            }

            // Exclude forums from active topics
            if (!($forumRow['forum_flags'] & FORUM_FLAG_ACTIVE_TOPICS) && ($search_id == 'active_topics')) {
                $forumIdExclusions[] = (int)$forumRow['forum_id'];
                continue;
            }
        }
        query::freeCursor($this->getDb(), $cursor);
    }
}

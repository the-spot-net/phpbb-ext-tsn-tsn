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
use phpbb\request\request_interface;
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
 * @property \phpbb\auth\auth          $auth
 * @property \phpbb\content_visibility $contentVisibility
 * @property \phpbb\controller\helper  $helper
 * @property \phpbb\language\language  $language
 * @property \phpbb\request\request    $request
 * @property \phpbb\template\template  $template
 * @property \phpbb\user               $user
 */
trait myspot
{

    /**
     * Generate the post sets for the MySpot page
     */
    private function moduleMySpotPosts()
    {
        $this->template->assign_vars([
            'S_ALLOW_SEARCH'      => ($this->auth->acl_get('u_search') || $this->auth->acl_getf_global('f_search') || $this->getConfig('load_search')),
            'S_SEARCH_OVERLOADED' => ($this->user->load
                && $this->getConfig('limit_search_load')
                && ($this->user->load > doubleval($this->getConfig('limit_search_load')))),
        ]);

        $forumIdExclusions = [];

        $this->processMySpotSearchSetup('', $forumIdExclusions);

        $forumIdWhitelistSql = $this->contentVisibility->get_global_visibility_sql('topic', $forumIdExclusions, 't.');

        $cursor = query::getMySpotFeedPage($this->getDb(), $forumIdWhitelistSql, $forumIdExclusions, (int)$this->user->data['user_id'], (int)$this->user->data['user_lastmark'], $this->request->variable('t', AbstractBase::$now), $this->request->variable('p', 1));
        $field = 'topic_id';

        $topicIds = $this->postprocessMySpotSearchResults($cursor, $field);

        $blockVars = $this->prepareMySpotSearchResultsOutput($topicIds, $forumIdExclusions, $forumIdWhitelistSql);

        $this->template->assign_block_vars_array('myspotfeed', $blockVars);
    }

    /**
     * Handles the render of the Special Report AJAX request from tsn/special-report
     */
    private function moduleSpecialReport()
    {
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
     * Process the topic query for the particular submodule results cursor
     *
     * @param $cursor
     * @param $field
     *
     * @return array
     */
    private function postprocessMySpotSearchResults($cursor, $field)
    {
        $topicIds = [];
        while ($row = $this->getDb()->sql_fetchrow($cursor)) {
            $topicIds[] = (int)$row[$field];
        }
        query::freeCursor($this->getDb(), $cursor);

        return $topicIds;
    }

    /**
     * @param $topicIds
     * @param $forumIdExclusions
     * @param $approvedTopicForumIdsSql
     *
     * @return array
     */
    private function prepareMySpotSearchResultsOutput($topicIds, $forumIdExclusions, $approvedTopicForumIdsSql)
    {
        $blockVars = [];
        // make sure that some arrays are always in the same order
        sort($forumIdExclusions);

        $this->language->add_lang('viewtopic');

        if (count($topicIds)) {

            // Do this for later...
            if ($this->getConfig('load_anon_lastread') || ($this->user->data['is_registered'] && !$this->getConfig('load_db_lastread'))) {
                $tracking_topics = $this->request->variable($this->getConfig('cookie_name') . '_track', '', true, request_interface::COOKIE);
                $tracking_topics = ($tracking_topics) ? tracking_unserialize($tracking_topics) : [];
            }

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
            unset($shadow_topic_list);

            foreach ($forums as $forum_id => $forum) {
                if ($this->user->data['is_registered'] && $this->getConfig('load_db_lastread')) {
                    $topic_tracking_info[$forum_id] = get_topic_tracking($forum_id, $forum['topic_list'], $forum['rowset'], [$forum_id => $forum['mark_time']]);
                } else if ($this->getConfig('load_anon_lastread') || $this->user->data['is_registered']) {
                    $topic_tracking_info[$forum_id] = get_complete_topic_tracking($forum_id, $forum['topic_list']);

                    if (!$this->user->data['is_registered']) {
                        /** @noinspection PhpUndefinedVariableInspection */
                        $this->user->data['user_lastmark'] = (isset($tracking_topics['l']))
                            ? (int)(base_convert($tracking_topics['l'], 36, 10) + $this->getConfig('board_startdate'))
                            : 0;
                    }
                }
            }
            unset($forums);

            if (!function_exists('topic_status')) {
                include_once('includes/functions_display.' . AbstractBase::$phpEx);
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

                $blockVars[] = [
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
                    'U_MCP_REPORT'  => append_sid(AbstractBase::$phpbbRootPath . '/mcp.' . AbstractBase::$phpEx, 'i=reports&amp;mode=reports&amp;t=' . $topicId, true, $this->user->session_id),
                    'U_VIEW_FORUM'  => $this->helper->route(url::ROUTE_FORUM, ['id' => $forum_id]),
                ];

            }
        }
        unset($rowset);

        return $blockVars;
    }

    /**
     * Setup the forum IDs that can be used when searching for the MySpot post submodules
     *
     * @param string $search_id The Search Content Enum
     * @param array  $forumIdExclusions
     */
    private function processMySpotSearchSetup($search_id, array &$forumIdExclusions = [])
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

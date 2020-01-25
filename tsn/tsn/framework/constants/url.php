<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 12/30/19
 * Time: 2:07 PM
 */

namespace tsn\tsn\framework\constants;

/**
 * Class url
 * Handles some URL Route Constants and methods for constructing them
 * @package tsn\tsn\controller
 */
class url
{
    // URI Base Routes
    const ROUTE_AJAX = 'tsn_tsn_ajax';
    const ROUTE_INDEX = 'tsn_tsn_index';
    const ROUTE_FORUM = 'tsn_tsn_forum';
    const ROUTE_GROUP = 'tsn_tsn_group';
    const ROUTE_LOGIN = 'tsn_tsn_login';
    const ROUTE_MEMBER = 'tsn_tsn_member';
    const ROUTE_MODERATOR = 'tsn_tsn_moderator';
    const ROUTE_POST = 'tsn_tsn_post';
    const ROUTE_TOPIC = 'tsn_tsn_topic';
    const ROUTE_USER = 'tsn_tsn_user';

    // AJAX Slugs
    const AJAX_MYSPOT_FEED_PAGE = 'myspot-feed';
    const AJAX_LOGIN = 'login';
}

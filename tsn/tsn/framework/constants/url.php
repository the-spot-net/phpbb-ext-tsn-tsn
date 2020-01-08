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
    // URI Route Directories
    const DIR_TSN = 'tsn/';
    const DIR_AJAX = self::DIR_TSN . 'ajax/';

    // URI Base Routes
    const ROUTE_INDEX = 'tsn_tsn_index';
    const ROUTE_FORUM = self::DIR_TSN . 'forum';
    const ROUTE_GROUP = self::DIR_TSN . 'group';
    const ROUTE_LOGIN = 'tsn_tsn_login';
    const ROUTE_MEMBER = 'tsn_tsn_member';
    const ROUTE_TOPIC = self::DIR_TSN . 'topic';
    const ROUTE_USER = 'tsn_tsn_user';

    // AJAX Slugs for use in switch & routes
    const SLUG_SPECIAL_REPORT = 'special-report';

    // URI AJAX Routes
    const ROUTE_AJAX_SPECIAL_REPORT = self::DIR_AJAX . self::SLUG_SPECIAL_REPORT;

    /**
     * @param       $routeUriConstant
     * @param array $appendedIds Ordered array of IDs to append to the URL (.../list, [forumId, topicId])
     *                           //param array $requestParams Field-value pairs to pass to url;::makeUrlParams()
     *
     * @return string
     * @uses \tsn\tsn\controller\url::makeUrlParams()
     */
//    public static function make($routeUriConstant, $appendedIds = [], $requestParams = [])
    public static function make($routeUriConstant, $appendedIds = [])
    {
        return implode(array_filter([
                $routeUriConstant,
                ($appendedIds) ? '/' . implode('/', $appendedIds) : '',
                // ($requestParams) ? self::makeUrlParams($requestParams) : '',
            ]
        ));
    }

//    /**
//     * @param array $params  Field-value pairs to generate the query string
//     * @param null  $urlHash URL Hash to jump to an anchor or trigger something to open
//     *
//     * @return string
//     */
//    public static function makeUrlParams(array $params = [], $urlHash = null)
//    {
//        $params = array_filter($params);
//        $urlHash = trim(ltrim($urlHash, '#'));
//
//        $queryString = '';
//        $queryString .= ($params) ? '?' . http_build_query($params) : '';
//        $queryString .= ($urlHash) ? '#' . $urlHash : '';
//
//        return $queryString;
//    }
}

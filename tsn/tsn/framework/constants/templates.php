<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/18/20
 * Time: 10:27 AM
 */

namespace tsn\tsn\framework\constants;

use phpbb\template\template;

/**
 * Class templates
 * Constants for routes to readable templates
 * @package tsn\tsn\framework\constants
 */
class templates
{

    // Directories
    const TEMPLATE_DIR = '@tsn_tsn/';
    const PARTIALS_DIR = self::TEMPLATE_DIR . 'partials/';

    // Partials
    const P_TOPIC_CARD = self::PARTIALS_DIR . 'tsn_topic_card.html';

    /**
     * @param \phpbb\template\template $template
     * @param                          $handle
     *
     * @return false|string|null
     */
    public static function get(template $template, $handle)
    {
        ob_start();
        $template->display($handle);
        $html = ob_get_clean();

        return preg_replace('/\s+/', ' ', $html);
    }
}

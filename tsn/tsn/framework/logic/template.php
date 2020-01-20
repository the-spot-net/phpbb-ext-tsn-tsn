<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/18/20
 * Time: 10:27 AM
 */

namespace tsn\tsn\framework\logic;

use phpbb\template\twig\twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class template
 * Adds functionality for rendering template partials
 * @package tsn\tsn\framework\logic
 */
class template extends twig
{

    // Directories
    const TEMPLATE_DIR = '@tsn_tsn/';
    const PARTIALS_DIR = self::TEMPLATE_DIR . 'partials/';

    // Partials
    const P_MYSPOT_FEED = self::PARTIALS_DIR . 'tsn_myspot_feed.html';
    const P_TOPIC_CARD = self::PARTIALS_DIR . 'tsn_topic_card.html';

    /**
     * @param string $templateConstant A template path name or constant
     *
     * @return false|string|null
     */
    public function renderPartial($templateConstant)
    {
        try {

            $output = $this->twig->render($templateConstant, $this->get_template_vars());
            // Usually this is via AJAX, so compress the whitespace
            $output = preg_replace('/\s+/', ' ', $output);

        } catch (LoaderError $e) {
            $output = false;
            error_log('Template Loader error: ' . $e->getMessage() . ' :: ' . $e->getCode());
        } catch (RuntimeError $e) {
            $output = false;
            error_log('Template Runtime error: ' . $e->getMessage() . ' :: ' . $e->getCode());
        } catch (SyntaxError $e) {
            $output = false;
            error_log('Template Syntax error: ' . $e->getMessage() . ' :: ' . $e->getCode());
        }

        return $output;
    }
}

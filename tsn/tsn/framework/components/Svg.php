<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/25/20
 * Time: 1:54 PM
 */

namespace tsn\tsn\framework\components;

use tsn\tsn\framework\logic\template;

class Svg extends AbstractComponent implements ComponentInterface
{
    const T_BASE = template::COMPONENTS_DIR . 'svg.html';

    /** @var string the SVG Element Id to fetch from the sprite */
    public $elementId;

    /**
     * Svg constructor.
     *
     * @param \tsn\tsn\framework\logic\template $template
     * @param string                            $svgElementId
     */
    public function __construct(template $template, string $svgElementId)
    {
        parent::__construct($template);

        $this->elementId = $svgElementId;
    }

    /**
     * Any work that needs to be done before calling Component::renderOutput()
     * @return string If all required conditions are met, returns the HTML String; else empty string
     * @uses \tsn\tsn\framework\components\AbstractComponent::renderOutput();
     */
    public function render()
    {
        return parent::renderOutput(self::T_BASE, [
            'T_ELEMENT_ID' => $this->elementId,
        ], 'svg');
    }
}

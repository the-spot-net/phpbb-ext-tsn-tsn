<?php

namespace tsn\tsn\framework\components;

/**
 * Interface ComponentInterface
 * @package Firescope\Framework\Handlers\Component
 */
interface ComponentInterface
{
    /**
     * Any work that needs to be done before calling Component::renderOutput()
     * @return string If all required conditions are met, returns the HTML String; else empty string
     * @uses \tsn\tsn\framework\components\AbstractComponent::renderOutput()
     */
    public function render();
}

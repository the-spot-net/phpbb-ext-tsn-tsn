<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/25/20
 * Time: 1:46 PM
 */

namespace tsn\tsn\framework\components;

use tsn\tsn\framework\logic\common;
use tsn\tsn\framework\logic\template;

class AbstractComponent
{
    const COMMON_OPTION_DISABLE_AUTOCOMPLETE = 'disableAutocomplete';

    /** @var bool  Declare the component as Enabled (true) or Disabled (false) */
    protected $isEnabled = true;
    protected $disableAutoComplete = false;
    protected $jsSelectors = [];
    protected $jsDataAttributes = [];
    protected $additionalClasses = [];

    /** @var \tsn\tsn\framework\logic\template */
    protected $template = null;

    public function __construct(template $template)
    {
        $this->template = $template;
    }

    /**
     * Generate array of `data-$key="$value"` strings
     *
     * @param array $jsData Key-Value pairs
     *
     * @return string
     */
    public static function generateJsDataAttributes(array $jsData)
    {
        $dataAttributes = [];
        foreach ($jsData as $key => $value) {
            if (strlen($value)) {
                $dataAttributes[] = 'data-' . $key . '="' . htmlentities($value) . '"';
            }
        }

        return implode(' ', $dataAttributes);
    }

    /**
     * Array of CSS class names
     *
     * @param array $additionalClasses
     *
     * @return $this
     */
    public function addClasses($additionalClasses = [])
    {
        $this->additionalClasses = array_merge($this->additionalClasses, (array)$additionalClasses);

        return $this;
    }

    /**
     * Associative array of key-value pairs; lowercase, alpha-only recommended
     *
     * @param array $jsDataAttributes
     *
     * @return $this
     */
    public function addJsDataAttributes($jsDataAttributes = [])
    {
        $this->jsDataAttributes = array_merge($this->jsDataAttributes, (array)$jsDataAttributes);

        return $this;
    }

    /**
     * Array of hyphenated javascript selectors to uniquely trigger events/access same components.
     * Always prefix with `js--` followed by a single-hyphenated, relevant, namespace to the purpose
     *
     * @param array $jsSelectors
     *
     * @return $this
     * @example array('js--tsn-form');
     */
    public function addJsSelectors($jsSelectors = [])
    {
        $this->jsSelectors = array_merge($this->jsSelectors, (array)$jsSelectors);

        return $this;
    }

    /**
     * Attempt to render the element such that the browser and LastPass will not autocomplete/autofill
     */
    public function disableAutoComplete()
    {
        $this->jsDataAttributes['lpignore'] = 'true';
        $this->disableAutoComplete = true;
    }

    /**
     * @param array $additionalClasses
     *
     * @return string
     */
    public function getClasses($additionalClasses = [])
    {
        return template::mergeSelectors($this->additionalClasses, $additionalClasses);
    }

    /**
     * @return string
     */
    public function getJsDataAttributes()
    {
        return self::generateJsDataAttributes($this->jsDataAttributes);
    }

    /**
     * @param array $additionalSelectors
     *
     * @return string
     */
    public function getJsSelectors(array $additionalSelectors = [])
    {
        return template::mergeSelectors($this->jsSelectors, $additionalSelectors);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->isEnabled;
    }

    /**
     * Used in the static component methods to handle
     * the Options array and the common options therein
     *
     * @param array $options
     *
     * @return $this
     */
    public function processCommonOptions(array $options)
    {
        $this
            ->addJsSelectors((array)common::getArrayValue($options, 'jsSelectors', []))
            ->addJsDataAttributes(common::getArrayValue($options, 'jsDataAttributes', []))
            ->addClasses((array)common::getArrayValue($options, 'classes', []));

        if (Common::getArrayValue($options, self::COMMON_OPTION_DISABLE_AUTOCOMPLETE)) {
            $this->addJsDataAttributes(['lpignore' => 'true']);
            $this->disableAutoComplete();
        }

        return $this;
    }

    /**
     * @param string   $templateConstant
     * @param string[] $data
     *
     * @return array|string
     */
    protected function renderOutput($templateConstant, $data, $blockName)
    {
        $this->template->assign_block_vars($blockName, array_merge($data, [
            'T_CLASSES'      => $this->getClasses(),
            'T_JS_SELECTORS' => $this->getJsSelectors(),
            'T_JS_DATA'      => $this->getJsDataAttributes(),
        ]));

        $output = $this->template->renderPartial($templateConstant);
error_log($output);
        $this->template->destroy_block_vars($blockName);

        return $output;
    }
}

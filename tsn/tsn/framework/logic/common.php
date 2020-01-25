<?php
/**
 * Created by thepizzy.net
 * User: @neotsn
 * Date: 1/25/20
 * Time: 1:49 PM
 */

namespace tsn\tsn\framework\logic;

class common
{
    /**
     * Helper to get the existing value in an array, else default
     *
     * @param array      $array
     * @param string|int $field
     * @param mixed      $default
     *
     * @return mixed
     */
    public static function getArrayValue($array, $field, $default = '')
    {
        $result = $default;

        if (is_array($array)) {
            $result = (isset($array[$field])) ? $array[$field] : $default;
        } else {
            error_log('Non-array value passed to common::getArrayValue(): ' . json_encode(['Trace' => debug_backtrace()]));
        }

        return $result;
    }
}

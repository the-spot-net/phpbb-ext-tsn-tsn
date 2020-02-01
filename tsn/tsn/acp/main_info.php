<?php
/**
 * the-spot.net. An extension for the phpBB Forum Software package.
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license       MIT
 */

namespace tsn\tsn\acp;

/**
 * the-spot.net ACP module info.
 */
class main_info
{
    /**
     * @return array
     */
    public function module()
    {
        return [
            'filename' => '\tsn\tsn\acp\main_module',
            'title'    => 'ACP_TSN_TITLE',
            'modes'    => [
                'settings' => [
                    'title' => 'ACP_TSN',
                    'auth'  => 'ext_tsn/tsn && acl_a_new_tsn_tsn',
                    'cat'   => ['ACP_TSN_TITLE'],
                ],
            ],
        ];
    }
}

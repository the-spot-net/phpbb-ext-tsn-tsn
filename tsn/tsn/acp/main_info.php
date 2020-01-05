<?php
/**
 *
 * the-spot.net. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tsn\tsn\acp;

/**
 * the-spot.net ACP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\tsn\tsn\acp\main_module',
			'title'		=> 'ACP_TSN_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'ACP_TSN',
					'auth'	=> 'ext_tsn/tsn && acl_a_new_tsn_tsn',
					'cat'	=> array('ACP_TSN_TITLE')
				),
			),
		);
	}
}

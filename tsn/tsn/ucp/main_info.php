<?php
/**
 *
 * the-spot.net. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tsn\tsn\ucp;

/**
 * the-spot.net UCP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\tsn\tsn\ucp\main_module',
			'title'		=> 'UCP_TSN_TITLE',
			'modes'		=> array(
				'settings'	=> array(
					'title'	=> 'UCP_TSN',
					'auth'	=> 'ext_tsn/tsn && acl_u_new_tsn_tsn',
					'cat'	=> array('UCP_TSN_TITLE')
				),
			),
		);
	}
}

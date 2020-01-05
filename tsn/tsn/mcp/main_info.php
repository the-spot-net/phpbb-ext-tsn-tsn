<?php
/**
 *
 * the-spot.net. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tsn\tsn\mcp;

/**
 * the-spot.net MCP module info.
 */
class main_info
{
	public function module()
	{
		return array(
			'filename'	=> '\tsn\tsn\mcp\main_module',
			'title'		=> 'MCP_TSN_TITLE',
			'modes'		=> array(
				'front'	=> array(
					'title'	=> 'MCP_TSN',
					'auth'	=> 'ext_tsn/tsn && acl_m_new_tsn_tsn',
					'cat'	=> array('MCP_TSN_TITLE')
				),
			),
		);
	}
}

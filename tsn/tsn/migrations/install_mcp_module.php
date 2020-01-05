<?php
/**
 *
 * the-spot.net. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tsn\tsn\migrations;

class install_mcp_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT module_id
			FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'mcp'
				AND module_langname = 'MCP_TSN_TITLE'";
		$result = $this->db->sql_query($sql);
		$module_id = $this->db->sql_fetchfield('module_id');
		$this->db->sql_freeresult($result);

		return $module_id !== false;
	}

	public static function depends_on()
	{
		return array('\phpbb\db\migration\data\v320\v320');
	}

	public function update_data()
	{
		return array(
			array('module.add', array(
				'mcp',
				0,
				'MCP_TSN_TITLE'
			)),
			array('module.add', array(
				'mcp',
				'MCP_TSN_TITLE',
				array(
					'module_basename'	=> '\tsn\tsn\mcp\main_module',
					'modes'				=> array('front'),
				),
			)),
		);
	}
}

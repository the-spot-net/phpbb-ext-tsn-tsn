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
 * the-spot.net UCP module.
 */
class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	/**
	 * Main UCP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	public function main($id, $mode)
	{
		global $phpbb_container;

		/** @var \tsn\tsn\controller\ucp_controller $ucp_controller */
		$ucp_controller = $phpbb_container->get('tsn.tsn.controller.ucp');

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');

		// Load a template for our UCP page
		$this->tpl_name = 'ucp_tsn_body';

		// Set the page title for our UCP page
		$this->page_title = $language->lang('UCP_TSN_TITLE');

		// Make the $u_action url available in our UCP controller
		$ucp_controller->set_page_url($this->u_action);

		// Load the display options handle in our UCP controller
		$ucp_controller->display_options();
	}
}

<?php
/**
 *
 * the-spot.net. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tsn\tsn\console\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * the-spot.net console command.
 */
class sample extends \phpbb\console\command\command
{
	/** @var \phpbb\user */
	protected $user;

	/**
	 * Constructor
	 *
	 * @param \phpbb\user $user User instance (mostly for translation)
	 */
	public function __construct(\phpbb\user $user)
	{
		parent::__construct($user);

		// Set up additional properties here
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->user->add_lang_ext('tsn/tsn', 'cli');
		$this
			->setName('tsn:tsn')
			->setDescription($this->user->lang('CLI_TSN'))
		;
	}

	/**
	 * Executes the command tsn:tsn.
	 *
	 * @param InputInterface  $input  An InputInterface instance
	 * @param OutputInterface $output An OutputInterface instance
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln($this->user->lang('CLI_TSN_HELLO'));
	}
}

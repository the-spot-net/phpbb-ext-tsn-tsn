<?php
/**
 *
 * the-spot.net. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, @neotsn, https://about.me/neotsn
 * @license MIT
 *
 */

namespace tsn\tsn\tests\functional;

/**
 * @group functional
 */
class view_test extends \phpbb_functional_test_case
{
	/**
	 * @inheritdoc
	 */
	protected static function setup_extensions()
	{
		return array('tsn/tsn');
	}

	/**
	 * Test crawls the extension's page route /demo/ with the variable: foo
	 * Asserts that only the expected text is found, "Hello foo"
	 */
	public function test_view_foo()
	{
		$crawler = self::request('GET', 'app.php/demo/foo');
		$this->assertContains('foo', $crawler->filter('h2')->text());

		$this->add_lang_ext('tsn/tsn', 'common');
		$this->assertContains($this->lang('TSN_HELLO', 'foo'), $crawler->filter('h2')->text());
		$this->assertNotContains($this->lang('TSN_GOODBYE', 'foo'), $crawler->filter('h2')->text());

		$this->assertNotContainsLang('ACP_TSN_GOODBYE', $crawler->filter('h2')->text());
	}

	/**
	 * Test crawls the extension's page route /demo/ again with a new variable: bar
	 * Asserts that only the expected text "bar" is found and that "foo" is no longer present.
	 */
	public function test_view_bar()
	{
		$crawler = self::request('GET', 'app.php/demo/bar');
		$this->assertNotContains('foo', $crawler->filter('h2')->text());
		$this->assertContains('bar', $crawler->filter('h2')->text());
	}
}

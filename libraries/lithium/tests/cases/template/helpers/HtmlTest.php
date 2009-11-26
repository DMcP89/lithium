<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2009, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\helpers;

use \lithium\http\Router;
use \lithium\template\helpers\Html;
use \lithium\tests\mocks\template\helpers\MockHtmlRenderer;

class HtmlTest extends \lithium\test\Unit {

	/**
	 * Test object instance
	 *
	 * @var object
	 */
	public $html = null;

	protected $_routes = array();

	/**
	 * Initialize test by creating a new object instance with a default context.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->_routes = Router::get();
		Router::connect(null);
		Router::connect('/{:controller}/{:action}/{:id}.{:type}');
		Router::connect('/{:controller}/{:action}.{:type}');

		$this->html = new Html(array('context' => new MockHtmlRenderer()));
	}

	/**
	 * Clean up after the test.
	 *
	 * @return void
	 */
	public function tearDown() {
		Router::connect(null);
		$this->_routes->each(function($route) { Router::connect($route); });
		unset($this->html);
	}

	/**
	 * testDocType method
	 *
	 * @access public
	 * @return void
	 */
	function testDocType() {
		$result = $this->html->docType('xhtml-strict');
		$expected = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" ';
		$expected .= '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';

		$this->assertEqual($result, $expected);

		$result = $this->html->docType('html4-strict');
		$expected = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" ';
		$expected .= '"http://www.w3.org/TR/html4/strict.dtd">';
		$this->assertEqual($result, $expected);

		$this->assertNull($this->html->docType('badness'));
	}

	/**
	 * Tests that character set declarations render the correct character set and meta tag.
	 *
	 * @return void
	 */
	public function testCharset() {
		$result = $this->html->charset();

		$this->assertTags($result, array('meta' => array(
			'http-equiv' => 'Content-Type', 'content' => 'text/html; charset=utf-8'
		)));

		$result = $this->html->charset('UTF-7');

		$this->assertTags($result, array('meta' => array(
			'http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-7'
		)));
	}

	/**
	 * Tests meta linking.
	 *
	 * @return void
	 */
	public function testMetaLink() {
		$result = $this->html->link(
			'RSS Feed',
			array('controller' => 'posts', 'type' => 'rss'),
			array('type' => 'rss')
		);

		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\/index\.rss/',
			'type' => 'application/rss+xml',
			'rel' => 'alternate',
			'title' => 'RSS Feed'
		)));

		$result = $this->html->link(
			'Atom Feed', array('controller' => 'posts', 'type' => 'xml'), array('type' => 'atom')
		);
		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\/index\.xml/',
			'type' => 'application/atom+xml',
			'title' => 'Atom Feed',
			'rel' => 'alternate'
		)));

		$result = $this->html->link('No-existy', '/posts.xmp', array('type' => 'rong'));
		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\.xmp/',
			'title' => 'No-existy',
		)));

		$result = $this->html->link('No-existy', '/posts.xpp', array('type' => 'atom'));
		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\.xpp/',
			'type' => 'application/atom+xml',
			'title' => 'No-existy',
			'rel' => 'alternate'
		)));

		$result = $this->html->link('Favicon', array(), array('type' => 'icon'));
		$expected = array(
			'link' => array(
				'href' => 'regex:/.*favicon\.ico/',
				'type' => 'image/x-icon',
				'rel' => 'icon',
				'title' => 'Favicon'
			),
			array('link' => array(
				'href' => 'regex:/.*favicon\.ico/',
				'type' => 'image/x-icon',
				'rel' => 'shortcut icon',
				'title' => 'Favicon'
			))
		);
		$this->assertTags($result, $expected);
	}

	/**
	 * Tests <a /> elements generated by `HtmlHelper::link()`
	 *
	 * @return void
	 */
	public function testLink() {
		$result = $this->html->link('/home');
		$expected = array('a' => array('href' => '/home'), 'regex:/\/home/', '/a');
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#');
		$expected = array('a' => array('href' => '#'), 'Next &gt;', '/a');
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array('escape' => true));
		$expected = array(
			'a' => array('href' => '#'),
			'Next &gt;',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array('escape' => 'utf-8'));
		$expected = array(
			'a' => array('href' => '#'),
			'Next &gt;',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array('escape' => false));
		$expected = array('a' => array('href' => '#'), 'Next >', '/a');
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array(
			'title' => 'to escape &#8230; or not escape?',
			'escape' => false
		));
		$expected = array(
			'a' => array('href' => '#', 'title' => 'to escape &#8230; or not escape?'),
			'Next >',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array(
			'title' => 'to escape &#8230; or not escape?', 'escape' => true
		));
		$expected = array(
			'a' => array('href' => '#', 'title' => 'to escape &amp;#8230; or not escape?'),
			'Next &gt;',
			'/a'
		);
		$this->assertTags($result, $expected);

		// $result = $this->html->link('Original size', array(
		// 	'controller' => 'images', 'action' => 'view', 3, '?' => array(
		// 		'height' => 100, 'width' => 200
		// 	)
		// ));
		// $expected = array(
		// 	'a' => array('href' => '/images/view/3?height=100&amp;width=200'),
		// 	'Original size',
		// 	'/a'
		// );
		// $this->assertTags($result, $expected);

		// Configure::write('Asset.timestamp', false);
		//
		// $result = $this->html->link($this->html->image('test.gif'), '#', array());
		// $expected = array(
		// 	'a' => array('href' => '#'),
		// 	'img' => array('src' => 'img/test.gif', 'alt' => ''),
		// 	'/a'
		// );
		// $this->assertTags($result, $expected);
		//
		// $result = $this->html->image('test.gif', array('url' => '#'));
		// $expected = array(
		// 	'a' => array('href' => '#'),
		// 	'img' => array('src' => 'img/test.gif', 'alt' => ''),
		// 	'/a'
		// );
		// $this->assertTags($result, $expected);
		//
		// Configure::write('Asset.timestamp', true);
	}

	/**
	 * Tests basic JavaScript linking using the <script /> tag
	 *
	 * @return void
	 */
	public function testScriptLinking() {
		$result = $this->html->script('script.js');
		$expected = '<script type="text/javascript" src="/js/script.js"></script>';
		$this->assertEqual($expected, $result);

		$result = $this->html->script('script');
		$expected = '<script type="text/javascript" src="/js/script.js"></script>';
		$this->assertEqual($expected, $result);

		$result = $this->html->script('scriptaculous.js?load=effects');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/js/scriptaculous.js?load=effects"></script>';
		$this->assertEqual($expected, $result);

		$result = $this->html->script('jquery-1.1.2');
		$expected = '<script type="text/javascript" src="/js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('jquery-1.1.2');
		$expected = '<script type="text/javascript" src="/js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('/plugin/js/jquery-1.1.2');
		$expected = '<script type="text/javascript" src="/plugin/js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('/some_other_path/myfile.1.2.2.min.js');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('some_other_path/myfile.1.2.2.min.js');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/js/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('some_other_path/myfile.1.2.2.min');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/js/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('http://example.com/jquery.js');
		$expected = '<script type="text/javascript" src="http://example.com/jquery.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script(array('prototype', 'scriptaculous'));
		$this->assertPattern(
			'/^\s*<script\s+type="text\/javascript"\s+src=".*js\/prototype\.js"[^<>]*><\/script>/',
			$result
		);
		$this->assertPattern('/<\/script>\s*<script[^<>]+>/', $result);
		$this->assertPattern(
			'/<script\s+type="text\/javascript"\s+src=".*js\/scriptaculous\.js"[^<>]*>' .
			'<\/script>\s*$/',
			$result
		);
	}

	/**
	 * Tests generating images with links wrapping them
	 *
	 * @return void
	 */
	public function testImageLinking() {
		$this->skipIf(true, "Not implemented");

		$result = $this->html->image('test.gif', array('url' => '#'));
		$expected = array(
			'a' => array('href' => '#'),
			'img' => array('src' => 'regex:/img\/test\.gif\?\d*/', 'alt' => ''),
			'/a'
		);
		$this->assertTags($result, $expected);
	}

	/**
	 * Tests generating image tags
	 *
	 * @return void
	 */
	function testImage() {

		$result = $this->html->image('test.gif');
		$this->assertTags($result, array('img' => array('src' => '/img/test.gif', 'alt' => '')));

		$result = $this->html->image('http://google.com/logo.gif');
		$this->assertTags($result, array('img' => array(
			'src' => 'http://google.com/logo.gif', 'alt' => ''
		)));

		$result = $this->html->image(array(
			'controller' => 'test', 'action' => 'view', 'id' => '1', 'type' => 'gif'
		));
		$this->assertTags($result, array('img' => array('src' => '/test/view/1.gif', 'alt' => '')));

		$result = $this->html->image('/test/view/1.gif');
		$this->assertTags($result, array('img' => array('src' => '/test/view/1.gif', 'alt' => '')));
	}

	/**
	 * Tests inline style linking with <link /> tags
	 *
	 * @return void
	 */
	public function testStyleLink() {
		$result = $this->html->style('screen');
		$expected = array('link' => array(
			'rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'regex:/.*css\/screen\.css/'
		));
		$this->assertTags($result, $expected);

		$result = $this->html->style('screen.css');
		$this->assertTags($result, $expected);

		$result = $this->html->style('screen.css?1234');
		$expected['link']['href'] = 'regex:/.*css\/screen\.css\?1234/';
		$this->assertTags($result, $expected);

		$result = $this->html->style('http://whatever.com/screen.css?1234');
		$expected['link']['href'] = 'regex:/http:\/\/.*\/screen\.css\?1234/';
		$this->assertTags($result, $expected);

// 		Configure::write('Asset.filter.css', 'css.php');
// 		$result = $this->html->style('lithium.generic');
// 		$expected['link']['href'] = 'regex:/.*ccss\/lithium\.generic\.css/';
// 		$this->assertTags($result, $expected);
// 		Configure::write('Asset.filter.css', false);
//
// 		$result = explode("\n", trim($this->html->style(array('lithium.generic', 'vendor.generic'))));
// 		$expected['link']['href'] = 'regex:/.*css\/lithium\.generic\.css/';
// 		$this->assertTags($result[0], $expected);
// 		$expected['link']['href'] = 'regex:/.*css\/vendor\.generic\.css/';
// 		$this->assertTags($result[1], $expected);
// 		$this->assertEqual(count($result), 2);
//
// 		Configure::write('Asset.timestamp', true);
//
// 		Configure::write('Asset.filter.css', 'css.php');
// 		$result = $this->html->style('lithium.generic');
// 		$expected['link']['href'] = 'regex:/.*ccss\/lithium\.generic\.css\?[0-9]+/';
// 		$this->assertTags($result, $expected);
// 		Configure::write('Asset.filter.css', false);
//
// 		$result = $this->html->style('lithium.generic');
// 		$expected['link']['href'] = 'regex:/.*css\/lithium\.generic\.css\?[0-9]+/';
// 		$this->assertTags($result, $expected);
//
// 		$debug = Configure::read('debug');
// 		Configure::write('debug', 0);
//
// 		$result = $this->html->style('lithium.generic');
// 		$expected['link']['href'] = 'regex:/.*css\/lithium\.generic\.css/';
// 		$this->assertTags($result, $expected);
//
// 		Configure::write('Asset.timestamp', 'force');
//
// 		$result = $this->html->style('lithium.generic');
// 		$expected['link']['href'] = 'regex:/.*css\/lithium\.generic\.css\?[0-9]+/';
// 		$this->assertTags($result, $expected);
//
// 		$webroot = $this->html->webroot;
// 		$this->html->webroot = '/testing/';
// 		$result = $this->html->style('lithium.generic');
// 		$expected['link']['href'] = 'regex:/\/testing\/css\/lithium\.generic\.css\?/';
// 		$this->assertTags($result, $expected);
// 		$this->html->webroot = $webroot;
//
// 		$webroot = $this->html->webroot;
// 		$this->html->webroot = '/testing/longer/';
// 		$result = $this->html->style('lithium.generic');
// 		$expected['link']['href'] = 'regex:/\/testing\/longer\/css\/lithium\.generic\.css\?/';
// 		$this->assertTags($result, $expected);
// 		$this->html->webroot = $webroot;
//
// 		Configure::write('debug', $debug);
	}

	/**
	 * Tests generating multiple <link /> or <style /> tags in a single call with an array
	 *
	 * @return void
	 */
	public function testStyleMulti() {
		$result = $this->html->style(array('base', 'layout'));
		$expected = array(
			'link' => array(
				'rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'regex:/.*css\/base\.css/'
			),
			array(
				'link' => array(
					'rel' => 'stylesheet', 'type' => 'text/css',
					'href' => 'regex:/.*css\/layout\.css/'
				)
			)
		);
		$this->assertTags($result, $expected);
	}

	/**
	 * Tests arbitrary tag generation.
	 *
	 * @return void
	 */
	public function testTag() {
		$result = $this->html->tag('div');
		$this->assertTags($result, '<div');

		$result = $this->html->tag('div', 'text');
		$this->assertTags($result, '<div', 'text', '/div');

		$result = $this->html->tag('div', '<text>', array('class' => 'class-name'), true);
		$this->assertTags($result, array(
			'div' => array('class' => 'class-name'), '&lt;text&gt;', '/div'
		));

		$result = $this->html->tag('div', '<text>', 'class-name', true);
		$this->assertTags($result, array(
			'div' => array('class' => 'class-name'), '&lt;text&gt;', '/div'
		));
	}

	/**
	 * Tests generation of block-level element (<div />).
	 *
	 * @return void
	 */
	public function testBlock() {
		$result = $this->html->block('class-name');
		$this->assertTags($result, array('div' => array('class' => 'class-name')));

		$result = $this->html->block('class-name', 'text');
		$this->assertTags($result, array('div' => array('class' => 'class-name'), 'text', '/div'));

		$result = $this->html->block('class-name', '<text>', array(), true);
		$this->assertTags($result, array(
			'div' => array('class' => 'class-name'), '&lt;text&gt;', '/div'
		));
	}

	/**
	 * Tests paragraph generation.
	 *
	 * @return void
	 */
	function testPara() {
		$result = $this->html->para('class-name', '');
		$this->assertTags($result, array('p' => array('class' => 'class-name')));

		$result = $this->html->para('class-name', 'text');
		$this->assertTags($result, array('p' => array('class' => 'class-name'), 'text', '/p'));

		$result = $this->html->para('class-name', '<text>', array(), true);
		$this->assertTags($result, array(
			'p' => array('class' => 'class-name'), '&lt;text&gt;', '/p'
		));
	}
}

?>
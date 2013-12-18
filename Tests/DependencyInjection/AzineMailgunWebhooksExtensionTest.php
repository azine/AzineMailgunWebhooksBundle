<?php
namespace Azine\MailgunWebhooksBundle\Tests\DependencyInjection;

use Symfony\Component\Yaml\Parser;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AzineMailgunWebhooksExtensionTest extends \PHPUnit_Framework_TestCase{

	/** @var ContainerBuilder */
	protected $configuration;

	/**
	 * This should not throw an exception
	 */
	public function testMinimalConfig(){
		$loader = new AzineMailgunWebhooksExtension();
		$config = $this->getMinimalConfig();
		$loader->load(array($config), new ContainerBuilder());
	}

	/**
	 * This should not throw an exception
	 */
	public function testFullConfigEmpty(){
		$loader = new AzineMailgunWebhooksExtension();
		$config = $this->getFullConfigEmpty();
		$loader->load(array($config), new ContainerBuilder());
	}


	public function testFullConfigWithValues(){
		$this->configuration = new ContainerBuilder();
		$loader = new AzineMailgunWebhooksExtension();
		$config = $this->getFullConfigWithValues();
		$loader->load(array($config), $this->configuration);

		$this->assertParameter("http://fb.profile.url.com", "azine_social_bar_fb_profile_url");
		$this->assertParameter("http://xing.profile.url.com", "azine_social_bar_xing_profile_url");
		$this->assertParameter("1234567890", "azine_social_bar_linked_in_company_id");
		$this->assertParameter("http://google.plus.profile.url.com", "azine_social_bar_google_plus_profile_url");
		$this->assertParameter("acme", "azine_social_bar_twitter_username");
	}



	/**
	 * Get the minimal config
	 * @return array
	 */
	protected function getMinimalConfig(){
		$yaml = <<<EOF
EOF;
		$parser = new Parser();

		return $parser->parse($yaml);
	}


	/**
	 * Get a full config for this bundle
	 */
	protected function getFullConfigEmpty(){
		$yaml = <<<EOF
# the url to you Facebook profile
fb_profile_url:       ~

# the url to your Google+ profile
google_plus_profile_url:  ~

# the url to your xing profile
xing_profile_url:     ~

# your profile-id => get it here http://developer.linkedin.com/plugins
linked_in_company_id:  ~

# your twitter username
twitter_username:     ~
EOF;
		$parser = new Parser();

		return $parser->parse($yaml);
	}

	/**
	 * Get a full config for this bundle
	 */
	protected function getFullConfigWithValues(){
		$yaml = <<<EOF
# the url to you Facebook profile
fb_profile_url:       http://fb.profile.url.com

# the url to your Google+ profile
google_plus_profile_url:  http://google.plus.profile.url.com

# the url to your xing profile
xing_profile_url:     http://xing.profile.url.com

# your profile-id => get it here http://developer.linkedin.com/plugins
linked_in_company_id:  1234567890

# your twitter username
twitter_username:     acme
EOF;
		$parser = new Parser();

		return $parser->parse($yaml);
	}

	/**
	 * @param mixed  $value
	 * @param string $key
	 */
	private function assertParameter($value, $key){
		$this->assertEquals($value, $this->configuration->getParameter($key), sprintf('%s parameter is correct', $key));
	}

	protected function tearDown(){
		unset($this->configuration);
	}

}

<?php

namespace Azine\MailgunWebhooksBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AzineMailgunWebhooksExtension extends Extension{

	const PREFIX = "azine_social_bar_";
	const FB_PROFILE = "fb_profile_url";
	const XING_PROFILE = "xing_profile_url";
	const LINKED_IN_PROFILE ="linked_in_company_id";
	const GOOGLE_PLUS_PROFILE = "google_plus_profile_url";
	const TWITTER_PROFILE ="twitter_username";

	/**
	 * {@inheritDoc}
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);

		if(array_key_exists(self::FB_PROFILE, $config))
			$container->setParameter(self::PREFIX.self::FB_PROFILE, $config[self::FB_PROFILE]);

		if(array_key_exists(self::XING_PROFILE, $config))
			$container->setParameter(self::PREFIX.self::XING_PROFILE, $config[self::XING_PROFILE]);

		if(array_key_exists(self::LINKED_IN_PROFILE, $config))
			$container->setParameter(self::PREFIX.self::LINKED_IN_PROFILE, $config[self::LINKED_IN_PROFILE]);

		if(array_key_exists(self::GOOGLE_PLUS_PROFILE, $config))
			$container->setParameter(self::PREFIX.self::GOOGLE_PLUS_PROFILE, $config[self::GOOGLE_PLUS_PROFILE]);

		if(array_key_exists(self::TWITTER_PROFILE, $config))
			$container->setParameter(self::PREFIX.self::TWITTER_PROFILE, $config[self::TWITTER_PROFILE]);


		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');

	}
}

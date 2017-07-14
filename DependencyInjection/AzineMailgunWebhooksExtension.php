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
class AzineMailgunWebhooksExtension extends Extension
{
    const PREFIX = "azine_mailgun_webhooks";
    const API_KEY = "api_key";
    const PUBLIC_API_KEY = "public_api_key";
    const EMAIL_DOMAIN = "email_domain";

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if(array_key_exists(self::API_KEY, $config))
            $container->setParameter(self::PREFIX."_".self::API_KEY, $config[self::API_KEY]);

        if(array_key_exists(self::PUBLIC_API_KEY, $config))
           $container->setParameter(self::PREFIX."_".self::PUBLIC_API_KEY, $config[self::PUBLIC_API_KEY]);
        
        if(array_key_exists(self::EMAIL_DOMAIN, $config))
            $container->setParameter(self::PREFIX."_".self::EMAIL_DOMAIN, $config[self::EMAIL_DOMAIN]);


        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

    }
}

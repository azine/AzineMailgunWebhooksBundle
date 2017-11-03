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
    const SPAM_ALERTS_PREFIX = "spam_alerts";
    const SEND_ENABLED = "enabled";
    const SEND_INTERVAL = "interval";
    const TICKET_ID = "ticket_id";
    const TICKET_SUBJECT = "ticket_subject";
    const TICKET_MESSAGE = "ticket_message";
    const ALERTS_RECIPIENT_EMAIL = "alerts_recipient_email";

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

        $container->setParameter(self::PREFIX."_".self::SPAM_ALERTS_PREFIX."_".self::SEND_ENABLED, $config[self::SPAM_ALERTS_PREFIX][self::SEND_ENABLED]);
        $container->setParameter(self::PREFIX."_".self::SPAM_ALERTS_PREFIX."_".self::SEND_INTERVAL, $config[self::SPAM_ALERTS_PREFIX][self::SEND_INTERVAL]);
        $container->setParameter(self::PREFIX."_".self::SPAM_ALERTS_PREFIX."_".self::TICKET_ID, $config[self::SPAM_ALERTS_PREFIX][self::TICKET_ID]);
        $container->setParameter(self::PREFIX."_".self::SPAM_ALERTS_PREFIX."_".self::TICKET_SUBJECT, $config[self::SPAM_ALERTS_PREFIX][self::TICKET_SUBJECT]);
        $container->setParameter(self::PREFIX."_".self::SPAM_ALERTS_PREFIX."_".self::TICKET_MESSAGE, $config[self::SPAM_ALERTS_PREFIX][self::TICKET_MESSAGE]);
        $container->setParameter(self::PREFIX."_".self::SPAM_ALERTS_PREFIX."_".self::ALERTS_RECIPIENT_EMAIL, $config[self::SPAM_ALERTS_PREFIX][self::ALERTS_RECIPIENT_EMAIL]);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

    }
}

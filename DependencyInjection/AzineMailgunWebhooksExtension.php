<?php

namespace Azine\MailgunWebhooksBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AzineMailgunWebhooksExtension extends Extension
{
    const PREFIX = 'azine_mailgun_webhooks';
    const API_KEY = 'api_key';
    const PUBLIC_API_KEY = 'public_api_key';
    const EMAIL_DOMAIN = 'email_domain';
    const SPAM_ALERTS_PREFIX = 'spam_alerts';
    const SEND_ENABLED = 'enabled';
    const SEND_INTERVAL = 'interval';
    const TICKET_ID = 'ticket_id';
    const TICKET_SUBJECT = 'ticket_subject';
    const TICKET_MESSAGE = 'ticket_message';
    const ALERTS_RECIPIENT_EMAIL = 'alerts_recipient_email';
    const HETRIXTOOLS_PREFIX = 'hetrixtools_service';
    const BLACKLIST_CHECK_API_KEY = 'api_key';
    const BLACKLIST_CHECK_IP_URL = 'blacklist_check_ip_url';
    const BLACKLIST_CHECK_IP_REPEAT_NOTIFICATION_DURATION = 'repeat_notification_after_days';
    const WEB_VIEW_ROUTE = 'web_view_route';
    const WEB_VIEW_TOKEN = 'web_view_token';
    const NO_REPLY_EMAIL = 'no_reply_email';
    const NO_REPLY_NAME = 'no_reply_name';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (array_key_exists(self::API_KEY, $config)) {
            $container->setParameter(self::PREFIX.'_'.self::API_KEY, $config[self::API_KEY]);
        }

        if (array_key_exists(self::PUBLIC_API_KEY, $config)) {
            $container->setParameter(self::PREFIX.'_'.self::PUBLIC_API_KEY, $config[self::PUBLIC_API_KEY]);
        }

        $container->setParameter(self::PREFIX.'_'.self::EMAIL_DOMAIN, $config[self::EMAIL_DOMAIN]);
        if (array_key_exists(self::NO_REPLY_EMAIL, $config)) {
            if (array_key_exists(self::NO_REPLY_NAME, $config)) {
                $container->setParameter(self::PREFIX . '_' . self::NO_REPLY_EMAIL, array($config[self::NO_REPLY_EMAIL] => $config[self::NO_REPLY_NAME]));
            } else {
                $container->setParameter(self::PREFIX . '_' . self::NO_REPLY_EMAIL, $config[self::NO_REPLY_EMAIL]);
            }
        } else {
            $container->setParameter(self::PREFIX . '_' . self::NO_REPLY_EMAIL, 'no-reply@'.$config[self::EMAIL_DOMAIN]);
        }

        $container->setParameter(self::PREFIX.'_'.self::SPAM_ALERTS_PREFIX.'_'.self::SEND_ENABLED, $config[self::SPAM_ALERTS_PREFIX][self::SEND_ENABLED]);
        $container->setParameter(self::PREFIX.'_'.self::SPAM_ALERTS_PREFIX.'_'.self::SEND_INTERVAL, $config[self::SPAM_ALERTS_PREFIX][self::SEND_INTERVAL] * 60);
        $container->setParameter(self::PREFIX.'_'.self::SPAM_ALERTS_PREFIX.'_'.self::TICKET_ID, $config[self::SPAM_ALERTS_PREFIX][self::TICKET_ID]);
        $container->setParameter(self::PREFIX.'_'.self::SPAM_ALERTS_PREFIX.'_'.self::TICKET_SUBJECT, $config[self::SPAM_ALERTS_PREFIX][self::TICKET_SUBJECT]);
        $container->setParameter(self::PREFIX.'_'.self::SPAM_ALERTS_PREFIX.'_'.self::TICKET_MESSAGE, $config[self::SPAM_ALERTS_PREFIX][self::TICKET_MESSAGE]);
        $container->setParameter(self::PREFIX.'_'.self::SPAM_ALERTS_PREFIX.'_'.self::ALERTS_RECIPIENT_EMAIL, $config[self::SPAM_ALERTS_PREFIX][self::ALERTS_RECIPIENT_EMAIL]);

        $container->setParameter(self::PREFIX.'_'.self::HETRIXTOOLS_PREFIX.'_'.self::BLACKLIST_CHECK_API_KEY, $config[self::HETRIXTOOLS_PREFIX][self::BLACKLIST_CHECK_API_KEY]);
        $container->setParameter(self::PREFIX.'_'.self::HETRIXTOOLS_PREFIX.'_'.self::BLACKLIST_CHECK_IP_URL, $config[self::HETRIXTOOLS_PREFIX][self::BLACKLIST_CHECK_IP_URL]);
        $container->setParameter(self::PREFIX.'_'.self::HETRIXTOOLS_PREFIX.'_'.self::BLACKLIST_CHECK_IP_REPEAT_NOTIFICATION_DURATION, $config[self::HETRIXTOOLS_PREFIX][self::BLACKLIST_CHECK_IP_REPEAT_NOTIFICATION_DURATION]);

        $container->setParameter(self::PREFIX.'_'.self::WEB_VIEW_ROUTE, $config[self::WEB_VIEW_ROUTE]);
        $container->setParameter(self::PREFIX.'_'.self::WEB_VIEW_TOKEN, $config[self::WEB_VIEW_TOKEN]);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}

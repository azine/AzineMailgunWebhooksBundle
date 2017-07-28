<?php

namespace Azine\MailgunWebhooksBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(AzineMailgunWebhooksExtension::PREFIX);

        $rootNode
            ->children()
                ->scalarNode(AzineMailgunWebhooksExtension::API_KEY)->isRequired()->cannotBeEmpty()->info("Your api-key for mailgun => see https://mailgun.com/cp")->end()
                ->scalarNode(AzineMailgunWebhooksExtension::PUBLIC_API_KEY)->defaultValue("")->info("Your public-api-key for mailgun => see https://mailgun.com/cp")->end()
                ->scalarNode(AzineMailgunWebhooksExtension::TICKET_ID)->defaultValue("")->info("Mailgun helpdesk ticket ID to request new IP address in case of spam complains")->end()
                ->scalarNode(AzineMailgunWebhooksExtension::TICKET_SUBJECT)->defaultValue("IP on spam-list, please fix.")->info("Mailgun HelpDesk ticket subject")->end()
                ->scalarNode(AzineMailgunWebhooksExtension::TICKET_MESSAGE)->defaultValue("It looks like my ip is on a spam-list. Please, assign a clean IP to my domain.")->info("Mailgun HelpDesk ticket subject")->end()
                ->scalarNode(AzineMailgunWebhooksExtension::ADMIN_USER_EMAIL)->defaultValue("")->info("Admin E-Mail to send notification about spam complaints")->end()
            ->end();

        return $treeBuilder;
    }
}

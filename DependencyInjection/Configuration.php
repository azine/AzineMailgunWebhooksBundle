<?php

namespace Azine\MailgunWebhooksBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(AzineMailgunWebhooksExtension::PREFIX);
        $rootNode = $this->getRootNode($treeBuilder, AzineMailgunWebhooksExtension::PREFIX);

        $rootNode
            ->children()
                ->scalarNode(AzineMailgunWebhooksExtension::API_KEY)->isRequired()->cannotBeEmpty()->info('Your api-key for mailgun => see https://mailgun.com/cp')->end()
                ->scalarNode(AzineMailgunWebhooksExtension::PUBLIC_API_KEY)->defaultValue('')->info('Your public-api-key for mailgun => see https://mailgun.com/cp')->end()
                ->scalarNode(AzineMailgunWebhooksExtension::EMAIL_DOMAIN)->defaultValue('')->info('Your email domain configured on Mailgun')->end()
            ->end();

        $this->addSpamAlertsSection($rootNode);
        $this->addBlacklistCheckSection($rootNode);

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addSpamAlertsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode(AzineMailgunWebhooksExtension::SPAM_ALERTS_PREFIX)
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->booleanNode(AzineMailgunWebhooksExtension::SEND_ENABLED)->defaultFalse()->info('Whether to send email notifications after receiving spam complaints')->end()
                        ->scalarNode(AzineMailgunWebhooksExtension::SEND_INTERVAL)->defaultValue('60')->info('Interval in minutes between sending of email notifications after receiving spam complaints')->end()
                        ->scalarNode(AzineMailgunWebhooksExtension::TICKET_ID)->defaultValue('')->info('Mailgun helpdesk ticket ID to request new IP address in case of spam complains')->end()
                        ->scalarNode(AzineMailgunWebhooksExtension::TICKET_SUBJECT)->defaultValue('IP on spam-list, please fix.')->info('Mailgun HelpDesk ticket subject')->end()
                        ->scalarNode(AzineMailgunWebhooksExtension::TICKET_MESSAGE)->defaultValue('It looks like my ip is on a spam-list. Please, assign a clean IP to my domain.')->info('Mailgun HelpDesk ticket subject')->end()
                        ->scalarNode(AzineMailgunWebhooksExtension::ALERTS_RECIPIENT_EMAIL)->defaultValue('')->info('Admin E-Mail to send notification about spam complaints')->end()
        ->end();
    }

    /**
     * @param ArrayNodeDefinition $node
     */
    private function addBlacklistCheckSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode(AzineMailgunWebhooksExtension::HETRIXTOOLS_PREFIX)
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode(AzineMailgunWebhooksExtension::BLACKLIST_CHECK_API_KEY)->defaultValue('')->info('Your public-api-key for hetrixtools => see https://hetrixtools.com/')->end()
                        ->scalarNode(AzineMailgunWebhooksExtension::BLACKLIST_CHECK_IP_URL)->defaultValue('https://api.hetrixtools.com/v2/<API_TOKEN>/blacklist-check/ipv4/<IP_ADDRESS>/')->info('Url for checking if ip is in blacklist => see https://docs.hetrixtools.com/blacklist-check-api/')->end()
        ->end();
    }

    /**
     * @param TreeBuilder $treeBuilder
     * @param $name
     */
    private function getRootNode(TreeBuilder $treeBuilder, $name)
    {
        // BC layer for symfony/config 4.1 and older
        if (! \method_exists($treeBuilder, 'getRootNode')) {
            return $treeBuilder->root($name);
        }

        return $treeBuilder->getRootNode();
    }
}

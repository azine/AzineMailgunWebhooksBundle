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
	        ->end();


        return $treeBuilder;
    }
}

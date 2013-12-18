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
        $rootNode = $treeBuilder->root('azine_social_bar');

        $rootNode
        	->children()
	        	->scalarNode(AzineMailgunWebhooksExtension::FB_PROFILE)->defaultValue("")->info("the url to you Facebook profile: will be used for the 'url' parameter when showing the 'follow' button")->end()
	        	->scalarNode(AzineMailgunWebhooksExtension::GOOGLE_PLUS_PROFILE)->defaultValue("")->info("the url to your Google+ profile: will be used for the 'url' parameter when showing the 'follow' button")->end()
	        	->scalarNode(AzineMailgunWebhooksExtension::XING_PROFILE)->defaultValue("")->info("the url to your xing profile: will be used for the 'url' parameter when showing the 'follow' button")->end()
	        	->scalarNode(AzineMailgunWebhooksExtension::LINKED_IN_PROFILE)->defaultValue("")->info("your profile-id (=> get it here http://developer.linkedin.com/plugins) : will be used for the 'companyId' parameter when showing the 'follow' button")->end()
	        	->scalarNode(AzineMailgunWebhooksExtension::TWITTER_PROFILE)->defaultValue("")->info("your twitter username: will be used for the 'action' parameter when showing the 'follow' button and also for the 'tag' and 'via' parameters of all twitter buttons ")->end()
	        	->end();


        return $treeBuilder;
    }
}

<?php

namespace Azine\MailgunWebhooksBundle\Tests\DependencyInjection;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AzineMailgunWebhooksExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    protected $configuration;

    /**
     * This should throw an exception.
     *
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testEmptyConfig()
    {
        $loader = new AzineMailgunWebhooksExtension();
        $config = $this->getEmptyConfig();
        $loader->load(array($config), new ContainerBuilder());
    }

    /**
     * This should not throw an exception.
     */
    public function testMinimalConfigEmpty()
    {
        $this->configuration = new ContainerBuilder();
        $loader = new AzineMailgunWebhooksExtension();
        $config = $this->getMinimalConfig();
        $loader->load(array($config), $this->configuration);

        $this->assertTrue($this->configuration instanceof ContainerBuilder);
        $this->assertParameter('someKey_adf4343lki5432543cfcab54325fabiacbzfac', 'azine_mailgun_webhooks_api_key');
    }

    public function testFullConfig()
    {
        $this->configuration = new ContainerBuilder();
        $loader = new AzineMailgunWebhooksExtension();
        $config = $this->getFullConfig();
        $loader->load(array($config), $this->configuration);

        $this->assertTrue($this->configuration instanceof ContainerBuilder);
        $this->assertParameter('someKey_adf4343lki5432543cfcab54325fabiacbzfac', 'azine_mailgun_webhooks_api_key');
        $this->assertParameter('somePublicKey_adflkiacfcajkhkhkjhkj8767654654bfabiacbzfac', 'azine_mailgun_webhooks_public_api_key');
    }

    /**
     * Get a full config for this bundle.
     */
    protected function getFullConfig()
    {
        $yaml = <<<EOF

# api-key
api_key:       someKey_adf4343lki5432543cfcab54325fabiacbzfac

# public api-key
public_api_key:  somePublicKey_adflkiacfcajkhkhkjhkj8767654654bfabiacbzfac

EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    /**
     * Get a the minimal config for this bundle.
     */
    protected function getMinimalConfig()
    {
        $yaml = <<<EOF

# api-key
api_key:       someKey_adf4343lki5432543cfcab54325fabiacbzfac

EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    /**
     * Get the minimal config.
     *
     * @return array
     */
    protected function getEmptyConfig()
    {
        $yaml = <<<EOF

EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    /**
     * @param string $value
     * @param string $key
     */
    private function assertParameter($value, $key)
    {
        $this->assertSame($value, $this->configuration->getParameter($key), sprintf('%s parameter is correct', $key));
    }

    protected function tearDown()
    {
        unset($this->configuration);
    }
}

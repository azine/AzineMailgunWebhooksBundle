<?php
namespace Azine\MailgunWebhooksBundle\Services;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AzineMailgunTwigExtension
 * Provides some filters and global variables
 * @package Azine\MailgunWebhooksBundle\Services
 */
class AzineMailgunTwigExtension extends \Twig_Extension
{
    private $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            'printArray' => new  \Twig_SimpleFilter('printArray', array($this, 'printArray'), array('is_safe' => array('html'))),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals()
    {
        if ($this->container->hasParameter(AzineMailgunWebhooksExtension::PREFIX . '_' . AzineMailgunWebhooksExtension::EMAIL_DOMAIN)) {
            $emailDomain = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX . '_' . AzineMailgunWebhooksExtension::EMAIL_DOMAIN);
        } else {
            $emailDomain = '';
        }
        /** @var MailgunEventRepository $repository */
        $repository = $this->container->get('doctrine')->getManager()->getRepository('AzineMailgunWebhooksBundle:MailgunEvent');
        if (is_null($repository->getLastKnownSenderIp())) {
            $lastKnownIp = '';
        } else {
            $lastKnownIp = $repository->getLastKnownSenderIp();
        }
        return array(
            'emailDomain' => $emailDomain,
            'lastKnownIp' => $lastKnownIp
        );
    }

    /**
     *
     * @param  array $var
     * @return mixed
     */
    public static function printArray(array $var)
    {
        return var_dump($var);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'azine_mailgun_webhooks_bundle_twig_extension';
    }
}

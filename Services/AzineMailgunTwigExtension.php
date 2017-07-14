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
    private $repository;
    private $emailDomain;
    
    public function __construct(MailgunEventRepository $repository, $emailDomain)
    {
        $this->repository = $repository;
        $this->emailDomain = is_null($emailDomain) ? '' : $emailDomain;
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
        $lastKnownIp = is_null($this->repository->getLastKnownSenderIp()) ? '' : $this->repository->getLastKnownSenderIp();
        return array(
            'emailDomain' => $this->emailDomain,
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

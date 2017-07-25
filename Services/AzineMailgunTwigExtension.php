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
    private $cachedLastKnownIp;
    
    public function __construct(MailgunEventRepository $repository, $emailDomain)
    {
        $this->repository = $repository;
        $this->emailDomain = is_null($emailDomain) ? '' : $emailDomain;
        $this->cachedLastKnownIp = null;
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

    public function getFunctions()
    {
        return array(
            'getEmailDomain' => new \Twig_SimpleFunction('getEmailDomain', function() { return $this->emailDomain;}),
            'getLastKnownIp' => new \Twig_SimpleFunction('getLastKnownIp', function() {
                if (is_null($this->cachedLastKnownIp)) {                    
                    $lastKnownIp = $this->repository->getLastKnownSenderIp();
                    $this->cachedLastKnownIp = $lastKnownIp;
                } else {
                    $lastKnownIp = $this->cachedLastKnownIp;
                }
                return is_null($lastKnownIp) ? '' : $lastKnownIp;
            })
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

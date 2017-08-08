<?php


namespace Azine\MailgunWebhooksBundle\Services;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class AzineMailgunCockpitService
{
    private $emailDomain;
    /** @var ManagerRegistry  */
    private $managerRegistry;
    /** @var \Twig_Environment  */
    private $twig;
    
    private $cachedLastKnownIp;
    
    public function __construct(ManagerRegistry $managerRegistry, \Twig_Environment $twig, $emailDomain)
    {
        $this->emailDomain = $emailDomain;
        $this->managerRegistry = $managerRegistry;
        $this->twig = $twig;
    }

    public function getLastKnownSenderIp()
    {
        if (is_null($this->cachedLastKnownIp)) {
            /** @var MailgunEventRepository $eventRepository */
            $eventRepository = $this->managerRegistry->getRepository(MailgunEvent::class);
            $lastKnownIp = $eventRepository->getLastKnownSenderIp();

            $this->cachedLastKnownIp = $lastKnownIp;
        } else {
            $lastKnownIp = $this->cachedLastKnownIp;
        }
        return $this->getValueOrEmptyString($lastKnownIp);
    }

    public function getEmailDomain()
    {
        return $this->getValueOrEmptyString($this->emailDomain);
    }

    public function getCockpitDataAsArray()
    {
        return array(
            'lastKnownIp' => $this->getLastKnownSenderIp(),
            'emailDomain' => $this->getEmailDomain()
        );
    }

    private function getValueOrEmptyString($value)
    {
        return is_null($value) ? '' : $value;
    }
}
<?php


namespace Azine\MailgunWebhooksBundle\Services;

use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;

class AzineMailgunCockpitService
{
    private $emailDomain;
    private $eventRepository;
    private $twig;
    
    private $cachedLastKnownIp;
    
    public function __construct(MailgunEventRepository $eventRepository, \Twig_Environment $twig, $emailDomain)
    {
        $this->emailDomain = $emailDomain;
        $this->eventRepository = $eventRepository;
        $this->twig = $twig;
    }

    public function getLastKnownSenderIp()
    {
        if (is_null($this->cachedLastKnownIp)) {                    
            $lastKnownIp = $this->eventRepository->getLastKnownSenderIp();
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

    public function getRenderedCockpitTemplate()
    {
        return $this->twig->render('@AzineMailgunWebhooks/cockpit.html.twig', array(
            'emailDomain' => $this->getEmailDomain(),
            'lastKnownIp' => $this->getLastKnownSenderIp()
        ));
    }

    private function getValueOrEmptyString($value)
    {
        return is_null($value) ? '' : $value;
    }
}
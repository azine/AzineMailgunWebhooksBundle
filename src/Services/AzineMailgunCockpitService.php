<?php

namespace Azine\MailgunWebhooksBundle\Services;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Doctrine\Persistence\ManagerRegistry;
use Twig\Environment;

class AzineMailgunCockpitService
{
    private $emailDomain;
    /** @var ManagerRegistry */
    private $managerRegistry;
    /** @var Environment */
    private $twig;

    private $cachedLastKnownIp;

    public function __construct(ManagerRegistry $managerRegistry, Environment $twig, $emailDomain)
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
            $lastKnownIp = null;
            $ipAddressData = $eventRepository->getLastKnownSenderIpData();

            if (isset($ipAddressData['ip'])) {
                $lastKnownIp = $ipAddressData['ip'];
            }

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
            'emailDomain' => $this->getEmailDomain(),
        );
    }

    private function getValueOrEmptyString($value)
    {
        return is_null($value) ? '' : $value;
    }
}

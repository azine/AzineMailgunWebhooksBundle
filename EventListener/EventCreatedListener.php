<?php

namespace Azine\MailgunWebhooksBundle\EventListener;

use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;

class EventCreatedListener
{
    private $mailer;
    
    public function __construct(AzineMailgunMailerService $mailer)
    {
        $this->mailer = $mailer;   
    }
    
    public function onEventCreated(MailgunWebhookEvent $event)
    {
        $eventType = $event->getMailgunEvent()->getEvent();
        if ($eventType === 'complained') {
            $this->mailer->sendSpamComplaintNotification($event->getMailgunEvent()->getId());
        }
    }
}
<?php

namespace Azine\MailgunWebhooksBundle\EventListener;

use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;

class EventCreatedListener
{
    private $mailer;
    private $sendNotifications;
    
    public function __construct(AzineMailgunMailerService $mailer, $sendNotifications)
    {
        $this->mailer = $mailer;   
        $this->sendNotifications = $sendNotifications;
    }
    
    public function onEventCreated(MailgunWebhookEvent $event)
    {
        $eventType = $event->getMailgunEvent()->getEvent();
        if ($eventType === 'complained') {
            if ($this->sendNotifications) {
                $this->mailer->sendSpamComplaintNotification($event->getMailgunEvent()->getId());                
            }
        }
    }
}
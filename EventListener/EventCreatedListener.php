<?php

namespace Azine\MailgunWebhooksBundle\EventListener;

use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;

/**
 * Class EventCreatedListener.
 */
class EventCreatedListener
{
    /**
     * @var AzineMailgunMailerService
     */
    private $mailer;

    /**
     * @var bool
     */
    private $sendNotifications;

    /**
     * EventCreatedListener constructor.
     *
     * @param AzineMailgunMailerService $mailer
     * @param bool                      $sendNotifications
     */
    public function __construct(AzineMailgunMailerService $mailer, $sendNotifications)
    {
        $this->mailer = $mailer;
        $this->sendNotifications = $sendNotifications;
    }

    /**
     * @param MailgunWebhookEvent $event
     */
    public function onEventCreated(MailgunWebhookEvent $event)
    {
        $eventType = $event->getMailgunEvent()->getEvent();
        if ('complained' === $eventType) {
            if ($this->sendNotifications) {
                $this->mailer->sendSpamComplaintNotification($event->getMailgunEvent()->getId());
            }
        }
        if(in_array($eventType, ['rejected', 'failed', 'dropped', 'bounced'])){
            $this->mailer->sendErrorNotification($event->getMailgunEvent());
        }
    }
}

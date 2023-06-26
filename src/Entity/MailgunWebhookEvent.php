<?php

namespace Azine\MailgunWebhooksBundle\Entity;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event for the symfony EventDispatcher-System.
 */
class MailgunWebhookEvent extends Event
{
    /**
     * @var MailgunEvent
     */
    private $mailgunEvent;

    /**
     * MailgunWebhookEvent constructor.
     * @param MailgunEvent $mailgunEvent
     */
    public function __construct(MailgunEvent $mailgunEvent)
    {
        $this->mailgunEvent = $mailgunEvent;
    }

    /**
     * @return MailgunEvent
     */
    public function getMailgunEvent()
    {
        return $this->mailgunEvent;
    }
}

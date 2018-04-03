<?php

namespace Azine\MailgunWebhooksBundle\Tests;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriberMock implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            MailgunEvent::CREATE_EVENT => 'handleCreate',
        );
    }

    public function handleCreate(MailgunWebhookEvent $event)
    {
        return $event->getMailgunEvent();
    }
}

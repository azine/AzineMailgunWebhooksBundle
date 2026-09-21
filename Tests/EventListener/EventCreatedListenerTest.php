<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\EventListener;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Azine\MailgunWebhooksBundle\EventListener\EventCreatedListener;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;
use PHPUnit\Framework\TestCase;

final class EventCreatedListenerTest extends TestCase
{
    public function testTemporaryFailureDoesNotTriggerDeliveryErrorNotification(): void
    {
        $mailer = $this->createMock(AzineMailgunMailerService::class);
        $mailer->expects(self::never())->method('sendErrorNotification');

        $event = (new MailgunEvent())
            ->setEvent('failed')
            ->setSeverity(MailgunEvent::FAILURE_SEVERITY_TEMPORARY);

        (new EventCreatedListener($mailer, false))->onEventCreated(new MailgunWebhookEvent($event));
    }

    public function testPermanentFailureTriggersDeliveryErrorNotification(): void
    {
        $event = (new MailgunEvent())
            ->setEvent('failed')
            ->setSeverity(MailgunEvent::FAILURE_SEVERITY_PERMANENT);

        $mailer = $this->createMock(AzineMailgunMailerService::class);
        $mailer->expects(self::once())->method('sendErrorNotification')->with($event);

        (new EventCreatedListener($mailer, false))->onEventCreated(new MailgunWebhookEvent($event));
    }

    public function testPermanentFailureNotificationCanBeDisabled(): void
    {
        $mailer = $this->createMock(AzineMailgunMailerService::class);
        $mailer->expects(self::never())->method('sendErrorNotification');

        $event = (new MailgunEvent())
            ->setEvent('failed')
            ->setSeverity(MailgunEvent::FAILURE_SEVERITY_PERMANENT);

        (new EventCreatedListener($mailer, false, false))->onEventCreated(new MailgunWebhookEvent($event));
    }

    public function testLegacyBounceStillTriggersDeliveryErrorNotification(): void
    {
        $event = (new MailgunEvent())->setEvent('bounced');

        $mailer = $this->createMock(AzineMailgunMailerService::class);
        $mailer->expects(self::once())->method('sendErrorNotification')->with($event);

        (new EventCreatedListener($mailer, false))->onEventCreated(new MailgunWebhookEvent($event));
    }
}

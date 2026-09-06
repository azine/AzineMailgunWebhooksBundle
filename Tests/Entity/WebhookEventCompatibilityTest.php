<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Entity;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class WebhookEventCompatibilityTest extends TestCase
{
    public function testWebhookEventDispatchesWithTheModernSymfonyContract(): void
    {
        $mailgunEvent = new MailgunEvent();
        $webhookEvent = new MailgunWebhookEvent($mailgunEvent);
        $dispatcher = new EventDispatcher();
        $received = null;
        $dispatcher->addListener(MailgunEvent::CREATE_EVENT, static function (MailgunWebhookEvent $event) use (&$received): void {
            $received = $event->getMailgunEvent();
            $event->stopPropagation();
        });

        self::assertSame($webhookEvent, $dispatcher->dispatch($webhookEvent, MailgunEvent::CREATE_EVENT));
        self::assertSame($mailgunEvent, $received);
        self::assertTrue($webhookEvent->isPropagationStopped());
    }

    #[DataProvider('initialStatuses')]
    public function testFirstDeliveryStatusAndRetryDoNotRequireAnExistingStatus(?string $initial): void
    {
        $summary = new MailgunMessageSummary('id@example.test', new \DateTime(), 'sender@example.test', 'recipient@example.test', 'subject', '127.0.0.1');
        // Doctrine can hydrate null from older records; new summaries start empty.
        (new \ReflectionProperty($summary, 'deliveryStatus'))->setValue($summary, $initial);
        self::assertSame('', $summary->getDeliveryStatus());
        $summary->updateDeliveryStatus('delivered');
        $summary->updateDeliveryStatus('delivered');
        self::assertSame('delivered', $summary->getDeliveryStatus());
        $summary->updateDeliveryStatus('opened');
        self::assertSame('delivered, opened', $summary->getDeliveryStatus());
    }

    public static function initialStatuses(): iterable
    {
        yield 'new summary' => [''];
        yield 'legacy null' => [null];
    }
}

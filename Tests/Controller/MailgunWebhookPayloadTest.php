<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Controller;

use Azine\MailgunWebhooksBundle\Controller\MailgunWebhookController;
use Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunMessageSummaryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
final class MailgunWebhookPayloadTest extends TestCase
{
    private const SIGNING_KEY = 'test-signing-key';

    #[DataProvider('eventPayloadProvider')]
    public function testCurrentMailgunPayloadsAreAcceptedAndSeverityIsPreserved(string $eventType, ?string $severity): void
    {
        $persisted = [];
        $capturedWebhookEvent = null;

        $eventRepository = $this->createStub(ObjectRepository::class);
        $eventRepository->method('findOneBy')->willReturn(null);

        $summaryRepository = $this->getMockBuilder(MailgunMessageSummaryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createOrUpdateMessageSummary'])
            ->getMock();
        $summaryRepository
            ->method('createOrUpdateMessageSummary')
            ->willReturn($this->createStub(MailgunMessageSummary::class));

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->method('getRepository')
            ->willReturnCallback(static fn (string $class): ObjectRepository => MailgunEvent::class === $class ? $eventRepository : $summaryRepository);
        $manager
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($manager);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$capturedWebhookEvent): object {
                if ($event instanceof MailgunWebhookEvent) {
                    $capturedWebhookEvent = $event;
                }

                return $event;
            });

        $timestamp = time();
        $token = 'token-'.$eventType.'-'.($severity ?? 'none');
        $signature = hash_hmac('SHA256', $timestamp.$token, self::SIGNING_KEY);

        $eventData = [
            'event' => $eventType,
            'recipient' => 'recipient@example.com',
            'envelope' => [
                'sender' => 'bounce@mg.example.com',
                'sending-ip' => '203.0.113.10',
            ],
            'message' => [
                'headers' => [
                    'message-id' => '<message-'.$eventType.'@example.com>',
                    'from' => 'Interim Homes <no-reply@example.com>',
                    'to' => 'recipient@example.com',
                    'subject' => 'Webhook test',
                ],
            ],
            'future-field' => ['kept' => true],
        ];

        if (null !== $severity) {
            $eventData['severity'] = $severity;
            $eventData['delivery-status'] = [
                'code' => '421',
                'message' => 'delivery status',
                'description' => 'test',
            ];
        }

        $payload = [
            'signature' => compact('timestamp', 'token', 'signature'),
            'event-data' => $eventData,
        ];

        $controller = new MailgunWebhookController(
            $registry,
            $dispatcher,
            new NullLogger(),
            self::SIGNING_KEY,
            28800,
        );

        $response = $controller->createFromWebhookAction(
            Request::create('/mailgun/event/webhook/create', 'POST', [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR)),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertInstanceOf(MailgunWebhookEvent::class, $capturedWebhookEvent);
        self::assertSame($eventType, $capturedWebhookEvent->getMailgunEvent()->getEvent());
        self::assertSame($severity, $capturedWebhookEvent->getMailgunEvent()->getSeverity());

        $customVariables = array_values(array_filter($persisted, static fn (object $entity): bool => $entity instanceof MailgunCustomVariable));
        self::assertNotEmpty($customVariables);
        self::assertTrue((bool) array_filter(
            $customVariables,
            static fn (MailgunCustomVariable $variable): bool => 'future-field' === $variable->getVariableName()
                && ['kept' => true] === $variable->getContent(),
        ));
    }

    public static function eventPayloadProvider(): iterable
    {
        yield 'accepted' => ['accepted', null];
        yield 'delivered' => ['delivered', null];
        yield 'temporary failure' => ['failed', MailgunEvent::FAILURE_SEVERITY_TEMPORARY];
        yield 'permanent failure' => ['failed', MailgunEvent::FAILURE_SEVERITY_PERMANENT];
        yield 'complained' => ['complained', null];
    }
}

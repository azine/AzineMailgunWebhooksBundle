<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Controller;

use Azine\MailgunWebhooksBundle\Controller\MailgunWebhookController;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunMessageSummaryRepository;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

final class MailgunWebhookRetryTest extends TestCase
{
    private const SIGNING_KEY = 'test-signing-key';

    public function testDeadlockedFlushIsRetriedWithFreshManagerAndDispatchedOnce(): void
    {
        $first = $this->manager(true);
        $second = $this->manager(false);
        $registry = $this->createMock(ManagerRegistry::class);
        $next = 0;
        $managers = [$first, $second];
        $registry->method('getManager')->willReturnCallback(static function () use (&$next, $managers): ObjectManager {
            return $managers[$next++];
        });
        $registry->expects(self::once())->method('resetManager')->willReturn($second);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                self::assertSame('message@example.com', $event->getMailgunEvent()->getMessageId());

                return $event;
            });

        $response = $this->controller($registry, $dispatcher)->createFromWebhookAction($this->request());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $next);
    }

    public function testExhaustedDeadlockRetriesReturnAnErrorWithoutDispatch(): void
    {
        $managers = [$this->manager(true), $this->manager(true), $this->manager(true)];
        $registry = $this->createMock(ManagerRegistry::class);
        $next = 0;
        $registry->method('getManager')->willReturnCallback(static function () use (&$next, $managers): ObjectManager {
            return $managers[$next++];
        });
        $registry->expects(self::exactly(2))->method('resetManager')->willReturn($managers[1], $managers[2]);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $response = $this->controller($registry, $dispatcher)->createFromWebhookAction($this->request());

        self::assertSame(500, $response->getStatusCode());
        self::assertSame(3, $next);
    }

    private function manager(bool $deadlock): ObjectManager
    {
        $eventRepository = $this->createStub(ObjectRepository::class);
        $eventRepository->method('findOneBy')->willReturn(null);

        $summaryRepository = $this->getMockBuilder(MailgunMessageSummaryRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createOrUpdateMessageSummary'])
            ->getMock();
        $summaryRepository->method('createOrUpdateMessageSummary')
            ->willReturn($this->createStub(MailgunMessageSummary::class));

        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturnCallback(
            static fn (string $class): ObjectRepository => MailgunEvent::class === $class ? $eventRepository : $summaryRepository,
        );
        if ($deadlock) {
            $manager->expects(self::once())->method('flush')->willThrowException(
                new class('Simulated deadlock') extends \RuntimeException implements RetryableException {
                },
            );
        } else {
            $manager->expects(self::once())->method('flush');
        }

        return $manager;
    }

    private function controller(ManagerRegistry $registry, EventDispatcherInterface $dispatcher): MailgunWebhookController
    {
        return new MailgunWebhookController($registry, $dispatcher, new NullLogger(), self::SIGNING_KEY, 28800);
    }

    private function request(): Request
    {
        $timestamp = time();
        $token = 'retry-test-token';
        $signature = hash_hmac('SHA256', $timestamp.$token, self::SIGNING_KEY);
        $payload = [
            'signature' => compact('timestamp', 'token', 'signature'),
            'event-data' => [
                'event' => 'delivered',
                'recipient' => 'recipient@example.com',
                'domain' => ['name' => 'example.com'],
                'message' => [
                    'headers' => [
                        'message-id' => '<message@example.com>',
                        'from' => 'sender@example.com',
                    ],
                ],
            ],
        ];

        return Request::create('/mailgun/event/webhook/create', 'POST', [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR));
    }
}

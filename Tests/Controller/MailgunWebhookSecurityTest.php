<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Controller;

use Azine\MailgunWebhooksBundle\Controller\MailgunWebhookController;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

final class MailgunWebhookSecurityTest extends TestCase
{
    private const WEBHOOK_SIGNING_KEY = 'test-signing-key';

    public function testMalformedJsonIsRejected(): void
    {
        $response = $this->controller()->createFromWebhookAction(
            Request::create('/mailgun/event/webhook/create', 'POST', [], [], [], [], '{not-json'),
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('Invalid webhook payload.', $response->getContent());
    }

    public function testNewApiInvalidSignatureIsRejected(): void
    {
        $payload = [
            'signature' => [
                'timestamp' => time(),
                'token' => 'new-api-token',
                'signature' => 'invalid',
            ],
            'event-data' => ['event' => 'delivered'],
        ];

        $response = $this->controller()->createFromWebhookAction(
            Request::create('/mailgun/event/webhook/create', 'POST', [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR)),
        );

        self::assertSame(401, $response->getStatusCode());
    }

    public function testOldApiInvalidSignatureIsRejected(): void
    {
        $response = $this->controller()->createFromWebhookAction(Request::create(
            '/mailgun/event/webhook/create',
            'POST',
            [
                'timestamp' => time(),
                'token' => 'old-api-token',
                'signature' => 'invalid',
                'event' => 'delivered',
            ],
        ));

        self::assertSame(401, $response->getStatusCode());
    }

    public function testDelayedSignatureWithinConfiguredWindowIsAcceptedAndDeduplicated(): void
    {
        $timestamp = time() - 60;
        $token = 'delayed-token';
        $signature = hash_hmac('SHA256', $timestamp.$token, self::WEBHOOK_SIGNING_KEY);

        $repository = $this->createStub(ObjectRepository::class);
        $repository->method('findOneBy')->willReturn(new MailgunEvent());

        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getRepository')->willReturn($repository);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($manager);

        $payload = [
            'signature' => compact('timestamp', 'token', 'signature'),
            'event-data' => ['event' => 'delivered'],
        ];

        $response = $this->controller($registry, 300)->createFromWebhookAction(
            Request::create('/mailgun/event/webhook/create', 'POST', [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR)),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Webhook already processed.', $response->getContent());
    }

    public function testSignatureOlderThanConfiguredWindowIsRejected(): void
    {
        $timestamp = time() - 301;
        $token = 'stale-token';
        $signature = hash_hmac('SHA256', $timestamp.$token, self::WEBHOOK_SIGNING_KEY);
        $payload = [
            'signature' => compact('timestamp', 'token', 'signature'),
            'event-data' => ['event' => 'delivered'],
        ];

        $response = $this->controller(null, 300)->createFromWebhookAction(
            Request::create('/mailgun/event/webhook/create', 'POST', [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR)),
        );

        self::assertSame(401, $response->getStatusCode());
    }

    public function testReplayedNewApiWebhookIsAcknowledgedWithoutProcessingAgain(): void
    {
        $timestamp = time();
        $token = 'replayed-token';
        $signature = hash_hmac('SHA256', $timestamp.$token, self::WEBHOOK_SIGNING_KEY);

        $repository = $this->createStub(ObjectRepository::class);
        $repository->method('findOneBy')->willReturn(new MailgunEvent());

        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getRepository')->willReturn($repository);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($manager);

        $payload = [
            'signature' => compact('timestamp', 'token', 'signature'),
            'event-data' => ['event' => 'delivered'],
        ];

        $response = $this->controller($registry)->createFromWebhookAction(
            Request::create('/mailgun/event/webhook/create', 'POST', [], [], [], [], json_encode($payload, JSON_THROW_ON_ERROR)),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Webhook already processed.', $response->getContent());
    }

    private function controller(?ManagerRegistry $registry = null, int $maxTimestampAge = 28800): MailgunWebhookController
    {
        return new MailgunWebhookController(
            $registry ?? $this->createStub(ManagerRegistry::class),
            $this->createStub(EventDispatcherInterface::class),
            new NullLogger(),
            self::WEBHOOK_SIGNING_KEY,
            $maxTimestampAge,
        );
    }
}

<?php

namespace Azine\MailgunWebhooksBundle\Tests\Services;

use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Translation\IdentityTranslator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class AzineMailgunSymfonyMailerTest extends TestCase
{
    public function testBlacklistNotificationUsesMultipartSymfonyEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return 'notification.blacklist_received' === $email->getSubject()
                    && 'no-reply@azine.me' === $email->getFrom()[0]->getAddress()
                    && 'spam-alerts@azine.me' === $email->getTo()[0]->getAddress()
                    && '<p>198.51.100.42</p>' === $email->getHtmlBody()
                    && '198.51.100.42' === $email->getTextBody();
            }));

        $twig = new Environment(new ArrayLoader([
            '@AzineMailgunWebhooks/Email/blacklistNotification.html.twig' => '<p>{{ ipAddress }}</p>',
            '@AzineMailgunWebhooks/Email/blacklistNotification.txt.twig' => '{{ ipAddress }}',
        ]));

        $service = new AzineMailgunMailerService(
            $mailer,
            $twig,
            new IdentityTranslator(),
            'no-reply@azine.me',
            '',
            '',
            '',
            'spam-alerts@azine.me',
            $this->createMock(ManagerRegistry::class),
            60,
        );

        $response = new HetrixtoolsServiceResponse();
        $response->status = HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS;

        self::assertSame(1, $service->sendBlacklistNotification(
            $response,
            '198.51.100.42',
            new \DateTimeImmutable('2026-08-11 12:00:00 UTC'),
        ));
    }
}

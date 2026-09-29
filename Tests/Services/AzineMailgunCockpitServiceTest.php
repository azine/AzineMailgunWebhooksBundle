<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Services;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunCockpitService;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

final class AzineMailgunCockpitServiceTest extends TestCase
{
    public function testUsesTheIpReturnedByTheEventRepository(): void
    {
        $repository = $this->getMockBuilder(MailgunEventRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastKnownSenderIpData'])
            ->getMock();
        $repository->expects(self::once())
            ->method('getLastKnownSenderIpData')
            ->willReturn(['ip' => '203.0.113.12', 'timestamp' => 1790000000]);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::once())
            ->method('getRepository')
            ->with(MailgunEvent::class)
            ->willReturn($repository);

        $service = new AzineMailgunCockpitService($registry, $this->createMock(Environment::class), 'mg.example.com');

        self::assertSame('203.0.113.12', $service->getLastKnownSenderIp());
        self::assertSame('203.0.113.12', $service->getCockpitDataAsArray()['lastKnownIp']);
    }
}

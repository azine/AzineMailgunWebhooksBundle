<?php

namespace Azine\MailgunWebhooksBundle\Tests\Command;

use Azine\MailgunWebhooksBundle\Command\CheckIpAddressIsBlacklistedCommand;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CheckIpAddressRetryTest extends TestCase
{
    public function testTransientHetrixFailureIsRetriedInProcess(): void
    {
        $repository = $this->getMockBuilder(MailgunEventRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLastKnownSenderIpData'])
            ->getMock();
        $repository
            ->method('getLastKnownSenderIpData')
            ->willReturn(['ip' => '198.51.100.42', 'timestamp' => '1552971782']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManager')->willReturn($entityManager);

        $response = new HetrixtoolsServiceResponse();
        $response->status = HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS;
        $response->blacklisted_count = 0;

        $calls = 0;
        $hetrixtoolsService = $this->getMockBuilder(AzineMailgunHetrixtoolsService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkIpAddressInBlacklist'])
            ->getMock();
        $hetrixtoolsService
            ->expects(self::exactly(2))
            ->method('checkIpAddressInBlacklist')
            ->willReturnCallback(static function () use (&$calls, $response): HetrixtoolsServiceResponse {
                if (0 === $calls++) {
                    throw new \InvalidArgumentException('Temporary response failure.');
                }

                return $response;
            });

        $mailerService = $this->getMockBuilder(AzineMailgunMailerService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $application = new Application();
        $application->add(new CheckIpAddressIsBlacklistedCommand(
            $registry,
            $hetrixtoolsService,
            $mailerService,
            'test',
            0,
        ));

        $tester = new CommandTester($application->find('mailgun:check-ip-in-blacklist'));
        $status = $tester->execute(['numberOfAttempts' => 1]);

        self::assertSame(Command::SUCCESS, $status);
        self::assertStringContainsString(CheckIpAddressIsBlacklistedCommand::STARTING_RETRY.'1', $tester->getDisplay());
        self::assertStringContainsString(CheckIpAddressIsBlacklistedCommand::IP_IS_NOT_BLACKLISTED, $tester->getDisplay());
    }
}

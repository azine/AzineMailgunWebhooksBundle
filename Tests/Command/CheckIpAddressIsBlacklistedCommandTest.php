<?php

namespace Azine\MailgunWebhooksBundle\Tests\Command;

use Azine\MailgunWebhooksBundle\Command\CheckIpAddressIsBlacklistedCommand;
use Azine\MailgunWebhooksBundle\Entity\HetrixToolsBlacklistResponseNotification;
use Azine\MailgunWebhooksBundle\Entity\Repositories\HetrixToolsBlacklistResponseNotificationRepository;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[AllowMockObjectsWithoutExpectations]
class CheckIpAddressIsBlacklistedCommandTest extends \PHPUnit\Framework\TestCase
{
    private $registry;
    private $entityManager;
    private $mailgunEventRepository;
    private $hetrixtoolsService;
    private $mailer;
    private $notificationRepository;
    private $command;

    protected function setUp(): void
    {
        $this->mailgunEventRepository = $this->createMock(MailgunEventRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturnCallback(function ($class) {
            if (HetrixToolsBlacklistResponseNotification::class === $class) {
                return $this->notificationRepository;
            }
            return $this->mailgunEventRepository;
        });
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->method('getManager')->willReturn($this->entityManager);
        $this->hetrixtoolsService = $this->getMockBuilder('Azine\\MailgunWebhooksBundle\\Services\\HetrixtoolsService\\AzineMailgunHetrixtoolsService')->disableOriginalConstructor()->getMock();
        $this->mailer = $this->getMockBuilder('Azine\\MailgunWebhooksBundle\\Services\\AzineMailgunMailerService')->disableOriginalConstructor()->getMock();
        $this->notificationRepository = $this->createMock(HetrixToolsBlacklistResponseNotificationRepository::class);
        $this->command = new CheckIpAddressIsBlacklistedCommand($this->registry, $this->hetrixtoolsService, $this->mailer, 'test', 30);
    }

    public function testSendingBlackListReportFirstTimeSent(): void
    {
        $this->runScenario(null, $this->blacklistedResponse(['list-a']), 1);
    }

    public function testSendingBlackListReportNotMutedSent(): void
    {
        $previous = (new HetrixToolsBlacklistResponseNotification())->setCreated(new \DateTimeImmutable('-31 days'));
        $this->runScenario($previous, $this->blacklistedResponse(['list-a']), 1);
    }

    public function testSendingBlackListReportLastNotificationIsLongSinceAndListsAreTheSameSent(): void
    {
        $previous = (new HetrixToolsBlacklistResponseNotification())->setCreated(new \DateTimeImmutable('-31 days'));
        $this->runScenario($previous, $this->blacklistedResponse(['list-a']), 1);
    }

    public function testSendingBlackListReportLastNotificationIsRecentButListsAreNotTheSameSent(): void
    {
        $previous = (new HetrixToolsBlacklistResponseNotification())->setCreated(new \DateTimeImmutable('-1 day'));
        $this->runScenario($previous, $this->blacklistedResponse(['list-b']), 1);
    }

    public function testSendingBlackListReportLastNotificationIsRecentButListsNotChangedMuted(): void
    {
        $previous = (new HetrixToolsBlacklistResponseNotification())->setCreated(new \DateTimeImmutable('-1 day'));
        $this->runScenario($previous, $this->blacklistedResponse(['list-a']), 0);
    }

    public function testSendingBlackListReportNotListedNotSent(): void
    {
        $this->runScenario(null, $this->cleanResponse(), 0);
    }

    public function testSendingBlackListReportNoResponseShowError(): void
    {
        $this->hetrixtoolsService->method('checkIpAddress')->willThrowException(new \RuntimeException('service unavailable'));
        $tester = $this->commandTester();
        self::assertNotSame(0, $tester->execute([]));
    }

    private function runScenario(?HetrixToolsBlacklistResponseNotification $previous, HetrixtoolsServiceResponse $response, int $expectedMailCalls): void
    {
        $this->mailgunEventRepository->method('getLastKnownSenderIpData')->willReturn(['ip' => '192.0.2.1', 'timestamp' => time()]);
        $this->hetrixtoolsService->method('checkIpAddress')->willReturn($response);
        $this->notificationRepository->method('findOneBy')->willReturn($previous);
        $this->mailer->expects(self::exactly($expectedMailCalls))->method('sendBlackListReport');
        $tester = $this->commandTester();
        $tester->execute([]);
    }

    private function commandTester(): CommandTester
    {
        $application = new Application();
        $application->add($this->command);
        return new CommandTester($this->command);
    }

    private function blacklistedResponse(array $lists): HetrixtoolsServiceResponse
    {
        $response = new HetrixtoolsServiceResponse();
        $response->status = HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS;
        $response->blacklisted_count = count($lists);
        $response->blacklisted_on = $lists;
        return $response;
    }

    private function cleanResponse(): HetrixtoolsServiceResponse
    {
        $response = new HetrixtoolsServiceResponse();
        $response->status = HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS;
        $response->blacklisted_count = 0;
        $response->blacklisted_on = [];
        return $response;
    }
}

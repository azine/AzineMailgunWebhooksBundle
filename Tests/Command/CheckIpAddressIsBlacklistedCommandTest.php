<?php

namespace Azine\MailgunWebhooksBundle\Tests\Command;

use Azine\MailgunWebhooksBundle\Command\CheckIpAddressIsBlacklistedCommand;
use Azine\MailgunWebhooksBundle\Entity\HetrixToolsBlacklistResponseNotification;
use Azine\MailgunWebhooksBundle\Entity\Repositories\HetrixToolsBlacklistResponseNotificationRepository;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

#[AllowMockObjectsWithoutExpectations]
class CheckIpAddressIsBlacklistedCommandTest extends \PHPUnit\Framework\TestCase
{
    private $registry;

    private $entityManager;

    private $entityRepository;

    private $hetrixtoolsService;

    private $azineMailgunService;

    private $hetrixtoolsRespose;
    private $blackListNotificationRepository;

    private $hetrixtoolsResposeData = array(
        'status' => HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS,
        'api_calls_left' => 5,
        'blacklist_check_credits_left' => 5,
        'blacklisted_count' => 5,
        'blacklisted_on' => array(
            array(
                'rbl' => 'dnsbl.cobion.com',
                'delist' => 'https://example.test.com/ip/198.51.100.42',
            ),
            array(
                'rbl' => 'pbl.spamhaus.org',
                'delist' => 'https://www.example.org/query/ip/198.51.100.42',
            ),
        ),
        'links' => array(
            'report_link' => 'https://example.com/report/blacklist/token/',
            'whitelabel_report_link' => '',
            'api_report_link' => 'https://api.example.com/v1/token/blacklist/report/198.51.100.42/',
            'api_blacklist_check_link' => 'https://api.example.com/v2/token/blacklist-check/ipv4/198.51.100.42/',
        ),
    );

    public function setUp(): void
    {
        $this->hetrixtoolsRespose = new HetrixtoolsServiceResponse();
        $this->hetrixtoolsRespose->status = HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS;
        $this->hetrixtoolsRespose->api_calls_left = 5;
        $this->hetrixtoolsRespose->blacklist_check_credits_left = 5;
        $this->hetrixtoolsRespose->blacklisted_count = 5;
        $this->hetrixtoolsRespose->blacklisted_on = array(
            array(
                'rbl' => 'dnsbl.cobion.com',
                'delist' => 'https://example.test.com/ip/198.51.100.42',
            ),
            array(
                'rbl' => 'pbl.spamhaus.org',
                'delist' => 'https://www.example.org/query/ip/198.51.100.42',
            ),
        );

        $this->hetrixtoolsRespose->links = array(
            'report_link' => 'https://example.com/report/blacklist/token/',
            'whitelabel_report_link' => '',
            'api_report_link' => 'https://api.example.com/v1/token/blacklist/report/198.51.100.42/',
            'api_blacklist_check_link' => 'https://api.example.com/v2/token/blacklist-check/ipv4/198.51.100.42/',
        );

        $this->entityRepository = $this->getMockBuilder(MailgunEventRepository::class)->disableOriginalConstructor()->onlyMethods(array('getLastKnownSenderIpData', 'findBy'))->getMock();
        $this->entityRepository->expects($this->any())->method('getLastKnownSenderIpData')->willReturn(array('ip' => '198.51.100.42', 'timestamp' => '1552971782'));

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->expects($this->any())->method('getRepository')->willReturn($this->entityRepository);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())->method('getManager')->willReturn($this->entityManager);

        $this->hetrixtoolsService = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService")->disableOriginalConstructor()->onlyMethods(array('checkIpAddressInBlacklist'))->getMock();

        $this->azineMailgunService = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService")->disableOriginalConstructor()->onlyMethods(array('sendBlacklistNotification'))->getMock();
        $this->azineMailgunService->expects($this->any())->method('sendBlacklistNotification')->willReturn(1);

        $this->blackListNotificationRepository = $this->getMockBuilder(HetrixToolsBlacklistResponseNotificationRepository::class)->disableOriginalConstructor()->getMock();
    }

    public function testSendingBlackListReportFirstTimeSent()
    {
        $tester = $this->getTester(28);

        //test if response status is 'SUCCESS' and ip is blacklisted
        $this->hetrixtoolsService->expects($this->any())->method('checkIpAddressInBlacklist')->willReturn($this->hetrixtoolsRespose);

        $this->entityRepository->expects($this->any())->method('findBy')->willReturn(array());

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(CheckIpAddressIsBlacklistedCommand::BLACKLIST_REPORT_WAS_SENT, $display);
    }

    public function testSendingBlackListReportNotMutedSent()
    {
        $tester = $this->getTester(0);

        //test if response status is 'SUCCESS' and ip is blacklisted
        $this->hetrixtoolsService->expects($this->any())->method('checkIpAddressInBlacklist')->willReturn($this->hetrixtoolsRespose);

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(CheckIpAddressIsBlacklistedCommand::BLACKLIST_REPORT_WAS_SENT, $display);
    }

    public function testSendingBlackListReportLastNotificationIsLongSinceAndListsAreTheSameSent()
    {
        $tester = $this->getTester(10);

        //test if response status is 'SUCCESS' and ip is blacklisted
        $this->hetrixtoolsService->expects($this->any())->method('checkIpAddressInBlacklist')->willReturn($this->hetrixtoolsRespose);

        $lastNotification = new HetrixToolsBlacklistResponseNotification();
        $lastNotification->setIgnoreUntil(new \DateTime('1 hour ago'));
        $lastNotification->setData($this->hetrixtoolsResposeData);
        $this->entityRepository->expects($this->any())->method('findBy')->willReturn(array($lastNotification));

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(CheckIpAddressIsBlacklistedCommand::BLACKLIST_REPORT_WAS_SENT, $display);
    }

    public function testSendingBlackListReportLastNotificationIsRecentButListsAreNotTheSameSent()
    {
        $tester = $this->getTester(10);

        //test if response status is 'SUCCESS' and ip is blacklisted
        $this->hetrixtoolsService->expects($this->any())->method('checkIpAddressInBlacklist')->willReturn($this->hetrixtoolsRespose);

        $lastNotification = new HetrixToolsBlacklistResponseNotification();
        $lastNotification->setIgnoreUntil(new \DateTime('1 hour'));
        $hetrixtoolsResposeData = array(
            'status' => HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS,
            'api_calls_left' => 5,
            'blacklist_check_credits_left' => 5,
            'blacklisted_count' => 5,
            'blacklisted_on' => array(
                array(
                    'rbl' => 'pbl.spamhaus.org',
                    'delist' => 'https://www.example.org/query/ip/198.51.100.42',
                ),
            ),
            'links' => array(
                'report_link' => 'https://example.com/report/blacklist/token/',
                'whitelabel_report_link' => '',
                'api_report_link' => 'https://api.example.com/v1/token/blacklist/report/198.51.100.42/',
                'api_blacklist_check_link' => 'https://api.example.com/v2/token/blacklist-check/ipv4/198.51.100.42/',
            ),
        );
        $lastNotification->setData($hetrixtoolsResposeData);
        $this->entityRepository->expects($this->any())->method('findBy')->willReturn(array($lastNotification));

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(CheckIpAddressIsBlacklistedCommand::BLACKLIST_REPORT_WAS_SENT, $display);
    }

    public function testSendingBlackListReportLastNotificationIsRecentButListsNotChangedMuted()
    {
        $tester = $this->getTester(10);

        //test if response status is 'SUCCESS' and ip is blacklisted
        $this->hetrixtoolsService->expects($this->any())->method('checkIpAddressInBlacklist')->willReturn($this->hetrixtoolsRespose);

        $lastNotification = new HetrixToolsBlacklistResponseNotification();
        $lastNotification->setIgnoreUntil(new \DateTime('1 hour'));
        $lastNotification->setData($this->hetrixtoolsResposeData);
        $this->entityRepository->expects($this->any())->method('findBy')->willReturn(array($lastNotification));

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(CheckIpAddressIsBlacklistedCommand::BLACKLIST_REPORT_IS_SAME_AS_PREVIOUS, $display);
    }

    public function testSendingBlackListReportNotListedNotSent()
    {
        $tester = $this->getTester();

        //test if response status is 'SUCCESS' and ip is blacklisted
        $this->hetrixtoolsService->expects($this->any())->method('checkIpAddressInBlacklist')->willReturn($this->hetrixtoolsRespose);

        //test if response status is 'SUCCESS' but ip is not blacklisted
        $this->hetrixtoolsRespose->blacklisted_count = 0;

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(CheckIpAddressIsBlacklistedCommand::IP_IS_NOT_BLACKLISTED, $display);
    }

    public function testSendingBlackListReportNoResponseShowError()
    {
        $tester = $this->getTester();
        $this->hetrixtoolsService->expects($this->once())->method('checkIpAddressInBlacklist')->will($this->throwException(new \InvalidArgumentException('no parseable response received.')));

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertStringContainsString(CheckIpAddressIsBlacklistedCommand::NO_VALID_RESPONSE_FROM_HETRIX, $display);
    }

    /**
     * @return CommandTester
     */
    private function getTester($muteDays = 0)
    {
        $application = new Application();
        $application->addCommand(new CheckIpAddressIsBlacklistedCommand($this->registry, $this->hetrixtoolsService, $this->azineMailgunService, 'test', $muteDays));
        $command = $this->getCheckIpAddressIsBlacklistedCommand($application);
        $tester = new CommandTester($command);

        return $tester;
    }

    /**
     * @param Application $application
     *
     * @return CheckIpAddressIsBlacklistedCommand
     */
    private function getCheckIpAddressIsBlacklistedCommand($application)
    {
        return $application->find('mailgun:check-ip-in-blacklist');
    }
}

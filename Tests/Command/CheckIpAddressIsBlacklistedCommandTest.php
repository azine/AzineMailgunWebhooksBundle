<?php
namespace Azine\MailgunWebhooksBundle\Tests\Command;

use Azine\MailgunWebhooksBundle\Command\CheckIpAddressIsBlacklistedCommand;
use Azine\MailgunWebhooksBundle\Command\DeleteOldEntriesCommand;
use Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\HetrixtoolsServiceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class CheckIpAddressIsBlacklistedCommandTest extends \PHPUnit_Framework_TestCase
{
    private $registry;

    private $entityManager;

    private $hetrixtoolsService;

    private $azineMailgunService;

    private $hetrixtoolsRespose;

    public function setUp()
    {
        $this->hetrixtoolsRespose = new HetrixtoolsServiceResponse();
        $this->hetrixtoolsRespose->status = HetrixtoolsServiceResponse::RESPONSE_STATUS_SUCCESS;
        $this->hetrixtoolsRespose->api_calls_left = 5;
        $this->hetrixtoolsRespose->blacklist_check_credits_left = 5;
        $this->hetrixtoolsRespose->blacklisted_count = 5;
        $this->hetrixtoolsRespose->blacklisted_on = [

            [
                'rbl' => 'dnsbl.cobion.com',
                'delist' => 'https://example.test.com/ip/44.44.44.44'
            ],
            [
                'rbl' => 'pbl.spamhaus.org',
                'delist' => 'https://www.example.org/query/ip/44.44.44.44'
            ]
        ];

        $this->hetrixtoolsRespose->links = [
            'report_link' => 'https://example.com/report/blacklist/token/',
            'whitelabel_report_link' => '',
            'api_report_link' => 'https://api.example.com/v1/token/blacklist/report/44.44.44.44/',
            'api_blacklist_check_link' => 'https://api.example.com/v2/token/blacklist-check/ipv4/44.44.44.44/'
        ];

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->setMethods(array('getLastKnownSenderIp'))->getMock();
        $repository->expects($this->any())->method('getLastKnownSenderIp')->will($this->returnValue('44.44.44.44'));

        $this->entityManager = $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->setMethods(array('getRepository'))->getMock();
        $this->entityManager->expects($this->any())->method("getRepository")->will($this->returnValue($repository));

        $this->registry = $this->getMockBuilder("Doctrine\Common\Persistence\ManagerRegistry")->disableOriginalConstructor()->getMock();
        $this->registry->expects($this->any())->method("getManager")->will($this->returnValue($this->entityManager));

        $this->hetrixtoolsService = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\HetrixtoolsService\AzineMailgunHetrixtoolsService")->disableOriginalConstructor()->setMethods(array('checkIpAddressInBlacklist'))->getMock();

        $this->azineMailgunService = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunMailerService")->disableOriginalConstructor()->setMethods(array('sendBlacklistNotification'))->getMock();
        $this->azineMailgunService->expects($this->any())->method('sendBlacklistNotification')->will($this->returnvalue(1));
    }

    public function testReportSending()
    {
        $tester = $this->getTester();

        //test if response status is 'SUCCESS' and ip is blacklisted
        $this->hetrixtoolsService->expects($this->any())->method("checkIpAddressInBlacklist")->will($this->returnValue($this->hetrixtoolsRespose));

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertContains(CheckIpAddressIsBlacklistedCommand::BLACKLIST_REPORT_WAS_SENT, $display);

        //test if response status is 'SUCCESS' but ip is not blacklisted
        $this->hetrixtoolsRespose->blacklisted_count = 0;

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertContains(CheckIpAddressIsBlacklistedCommand::IP_IS_NOT_BLACKLISTED, $display);
    }

    public function testNoResponse()
    {
        $tester = $this->getTester();
        $this->hetrixtoolsService->expects($this->once())->method("checkIpAddressInBlacklist")->will($this->returnValue(null));

        $tester->execute(array(''));

        $display = $tester->getDisplay();
        $this->assertContains(CheckIpAddressIsBlacklistedCommand::NO_RESPONSE_FROM_HETRIX, $display);
    }

    /**
     * @return CommandTester
     */
    private function getTester()
    {
        $application = new Application();
        $application->add(new CheckIpAddressIsBlacklistedCommand($this->registry, $this->hetrixtoolsService, $this->azineMailgunService));
        $command = $this->getCheckIpAddressIsBlacklistedCommand($application);
        $tester = new CommandTester($command);

        return $tester;
    }

    /**
     * @param  Application  $application
     * @return CheckIpAddressIsBlacklistedCommand
     */
    private function getCheckIpAddressIsBlacklistedCommand($application)
    {
        return $application->find('mailgun:check-ip-in-blacklist');
    }


}
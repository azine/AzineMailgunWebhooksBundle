<?php
namespace Azine\MailgunWebhooksBundle\Tests\Command;

use Azine\MailgunWebhooksBundle\Command\DeleteOldEntriesCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class DeleteOldEntriesCommandTest extends \PHPUnit_Framework_TestCase
{
    private $mailgunServiceMock;

    public function setUp()
    {
        $this->mailgunServiceMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunService")
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testHelpInfo()
    {
        $application = new Application();
        $application->add(new DeleteOldEntriesCommand($this->mailgunServiceMock));
        $command = $this->getDeleteOldEntriesCommand($application);

        $display = $command->getHelp();
        $this->assertContains("Mailgun accepted the request to send/forward the email and the message has been placed in queue.", $display);
    }

    public function testDeleteOldEntries_WithoutParams()
    {
        $application = new Application();
        $application->add(new DeleteOldEntriesCommand($this->mailgunServiceMock));
        $command = $this->getDeleteOldEntriesCommand($application);

        self::$days = 60;
        self::$count = 14;
        self::$type = null;
        $this->mailgunServiceMock->expects($this->once())->method("removeEvents")->will($this->returnCallback(array($this, "removeEventsCallback")));

        $tester = new CommandTester($command);

        $tester->execute(array(''));
        $display = $tester->getDisplay();
        $this->assertContains("deleting entries of any type.", $display);
        $this->assertContains("using default age-limit of '60 days ago'.", $display);
        $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than", $display);
        $this->assertContains("of any type have been deleted (14).", $display);
    }

    public static $days;
    public static $count;
    public static $type;

    public static function removeEventsCallback($type, $date)
    {
        $checkDate = new \DateTime(self::$days." days ago");
        self::assertEquals(self::$type, $type, "type null expected.");
        self::assertEquals($checkDate->format("Y-m-d H:i"), $date->format("Y-m-d H:i"), "wrong date.");

        return self::$count;
    }

    /**
     * @param  Application             $application
     * @return DeleteOldEntriesCommand
     */
    private function getDeleteOldEntriesCommand($application)
    {
        return $application->find('mailgun:delete-events');
    }

    public function testDeleteOldEntries_WithDate()
    {
        $application = new Application();
        $application->add(new DeleteOldEntriesCommand($this->mailgunServiceMock));
        $command = $this->getDeleteOldEntriesCommand($application);

        self::$days = 21;
        self::$count = 11;
        self::$type = null;
        $this->mailgunServiceMock->expects($this->once())->method("removeEvents")->will($this->returnCallback(array($this, "removeEventsCallback")));

        $tester = new CommandTester($command);

        $tester->execute(array("date" => "21 days ago"));
        $display = $tester->getDisplay();
        $this->assertContains("deleting entries of any type.", $display);
        $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than", $display);
        $this->assertContains("of any type have been deleted (11).", $display);
    }

    public function testDeleteOldEntries_WithDateAndType()
    {
        $application = new Application();
        $application->add(new DeleteOldEntriesCommand($this->mailgunServiceMock));
        $command = $this->getDeleteOldEntriesCommand($application);

        self::$days = 33;
        self::$count = 77;
        self::$type = "opened";
        $this->mailgunServiceMock->expects($this->once())->method("removeEvents")->will($this->returnCallback(array($this, "removeEventsCallback")));

        $tester = new CommandTester($command);
        $tester->execute(array("date" => "33 days ago", "type" => self::$type));
        $display = $tester->getDisplay();
        $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than", $display);
        $this->assertContains("of type '".self::$type."' have been deleted (77).", $display);
    }

    /**
     * @expectedException InvalidArgumentException
     *
     */
    public function testDeleteOldEntries_WithInvalidType()
    {
        $application = new Application();
        $application->add(new DeleteOldEntriesCommand($this->mailgunServiceMock));
        $command = $this->getDeleteOldEntriesCommand($application);

        $tester = new CommandTester($command);
        $tester->execute(array("date" => "33 days ago", "type" => "invalidType"));
    }
}

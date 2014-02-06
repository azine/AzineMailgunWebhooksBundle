<?php
namespace Azine\MailgunWebhooksBundle\Test\Command;

use Azine\MailgunWebhooksBundle\Command\DeleteOldEntriesCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;

class DeleteOldEntriesCommandTest extends \PHPUnit_Framework_TestCase {

	public function testHelpInfo() {
		$application = new Application();
		$application->add(new DeleteOldEntriesCommand());

		$command = $this->getDeleteOldEntriesCommand($application);

		$display = $command->getHelp();
		$this->assertContains("Mailgun accepted the request to send/forward the email and the message has been placed in queue.", $display);
	}

	public function testDeleteOldEntries_WithoutParams() {
		$application = new Application();
		$application->add(new DeleteOldEntriesCommand());
		$command = $this->getDeleteOldEntriesCommand($application);


		$mailgunServiceMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunService")->disableOriginalConstructor()->getMock();
		$mailgunServiceMock->expects($this->once())->method("removeEvents")->will($this->returnCallback(function ($type, $date){
			self::assertEquals(null, $type, "type null expected.");
			$checkDate = new \DateTime("60 days ago");
			self::assertEquals($checkDate->format("Y-m-d H:i"), $date->format("Y-m-d H:i"), "wrong date.");
			return 19;
		}));

		$containerMock = $this->getMockBuilder("Symfony\Component\DependencyInjection\ContainerInterface")->disableOriginalConstructor()->getMock();
		$containerMock->expects($this->once())->method("get")->with("azine_mailgun.service")->will($this->returnValue($mailgunServiceMock));

		$command->setContainer($containerMock);
		$tester = new CommandTester($command);

		$tester->execute(array(''));
		$display = $tester->getDisplay();
		$this->assertContains("deleting entries of any type.", $display);
		$this->assertContains("using default age-limit of '60 days ago'.", $display);
	    $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than", $display);
	    $this->assertContains("of any type have been deleted (19).", $display);

	}

	/**
	 * @param Application $application
	 * @return DeleteOldEntriesCommand
	 */
	private function getDeleteOldEntriesCommand($application){
	    return $application->find('mailgun:delete-events');
	}

	public function testDeleteOldEntries_WithDate(){
	    $application = new Application();
	    $application->add(new DeleteOldEntriesCommand());
	    $command = $this->getDeleteOldEntriesCommand($application);

	    $mailgunServiceMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunService")->disableOriginalConstructor()->getMock();
		$mailgunServiceMock->expects($this->once())->method("removeEvents")->will($this->returnCallback(function ($type, $date){
			self::assertEquals(null, $type, "type null expected.");
			$checkDate = new \DateTime("21 days ago");
			self::assertEquals($checkDate->format("Y-m-d H:i"), $date->format("Y-m-d H:i"), "wrong date.");
			return 11;
		}));

	    $containerMock = $this->getMockBuilder("Symfony\Component\DependencyInjection\ContainerInterface")->disableOriginalConstructor()->getMock();
	    $containerMock->expects($this->once())->method("get")->with("azine_mailgun.service")->will($this->returnValue($mailgunServiceMock));

	    $command->setContainer($containerMock);
	    $tester = new CommandTester($command);

	    $tester->execute(array("date" => "21 days ago"));
	    $display = $tester->getDisplay();
	    $this->assertContains("deleting entries of any type.", $display);
	    $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than", $display);
	    $this->assertContains("of any type have been deleted (11).", $display);
	}

	public function testDeleteOldEntries_WithDateAndType(){
	    $application = new Application();
	    $application->add(new DeleteOldEntriesCommand());
	    $command = $this->getDeleteOldEntriesCommand($application);

	    $count = 19;
	    $type = "opened";
	    $dateString = "21 days ago";


	    $mailgunServiceMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunService")->disableOriginalConstructor()->getMock();
		$mailgunServiceMock->expects($this->once())->method("removeEvents")->will($this->returnCallback(function ($type, $date){
			self::assertEquals("opened", $type, "type null expected.");
			$checkDate = new \DateTime("21 days ago");
			self::assertEquals($checkDate->format("Y-m-d H:i"), $date->format("Y-m-d H:i"), "wrong date.");
			return 19;
		}));

	    $containerMock = $this->getMockBuilder("Symfony\Component\DependencyInjection\ContainerInterface")->disableOriginalConstructor()->getMock();
	    $containerMock->expects($this->once())->method("get")->with("azine_mailgun.service")->will($this->returnValue($mailgunServiceMock));

	    $command->setContainer($containerMock);
	    $tester = new CommandTester($command);
	    $tester->execute(array("date" => $dateString, "type" => $type));
	    $display = $tester->getDisplay();
	    $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than", $display);
	    $this->assertContains("of type '$type' have been deleted (19).", $display);
	}

	/**
	 * @expectedException InvalidArgumentException
	 *
	 */
	public function testDeleteOldEntries_WithInvalidType() {
		$application = new Application();
		$application->add(new DeleteOldEntriesCommand());
		$command = $this->getDeleteOldEntriesCommand($application);

		$count = 19;
		$type = "invalidType";
		$dateString = "21 days ago";
		$date = new \DateTime($dateString);

		$containerMock = $this->getMockBuilder("Symfony\Component\DependencyInjection\ContainerInterface")->disableOriginalConstructor()->getMock();

		$command->setContainer($containerMock);
		$tester = new CommandTester($command);
		$tester->execute(array("date" => $dateString, "type" => $type));
	}
}

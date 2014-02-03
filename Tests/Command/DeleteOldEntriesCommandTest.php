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

		$count = 19;
		$type = null;

		$mailgunServiceMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunService")->disableOriginalConstructor()->getMock();
		$containerMock = $this->getMockBuilder("Symfony\Component\DependencyInjection\ContainerInterface")->disableOriginalConstructor()->getMock();
		$containerMock->expects($this->once())->method("get")->with("azine_mailgun.service")->will($this->returnValue($mailgunServiceMock));

		$command->setContainer($containerMock);
		$tester = new CommandTester($command);

		$date = new \DateTime("60 days ago");
		$mailgunServiceMock->expects($this->once())->method("removeEvents")->with($type, $date)->will($this->returnValue($count));
		$tester->execute(array(''));
		$display = $tester->getDisplay();
		$this->assertContains("deleting entries of any type.", $display);
		$this->assertContains("using default age-limit of '60 days ago'.", $display);
		$this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than " . $date->format("Y-m-d H:i:s") . " of any type have been deleted ($count).", $display);

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

	    $count = 11;
	    $type = null;
	    $dateString = "21 days ago";

	    $mailgunServiceMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunService")->disableOriginalConstructor()->getMock();
	    $containerMock = $this->getMockBuilder("Symfony\Component\DependencyInjection\ContainerInterface")->disableOriginalConstructor()->getMock();
	    $containerMock->expects($this->once())->method("get")->with("azine_mailgun.service")->will($this->returnValue($mailgunServiceMock));

	    $command->setContainer($containerMock);
	    $tester = new CommandTester($command);
	    $date = new \DateTime($dateString);
	    $mailgunServiceMock->expects($this->once())->method("removeEvents")->with($type, $date)->will($this->returnValue($count));
	    $tester->execute(array("date" => $dateString));
	    $display = $tester->getDisplay();
	    $this->assertContains("deleting entries of any type.", $display);
	    $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than ".$date->format("Y-m-d H:i:s")." of any type have been deleted ($count).", $display);
	}

	public function testDeleteOldEntries_WithDateAndType(){
	    $application = new Application();
	    $application->add(new DeleteOldEntriesCommand());
	    $command = $this->getDeleteOldEntriesCommand($application);

	    $count = 19;
	    $type = "opened";
	    $dateString = "21 days ago";


	    $mailgunServiceMock = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Services\AzineMailgunService")->disableOriginalConstructor()->getMock();
	    $containerMock = $this->getMockBuilder("Symfony\Component\DependencyInjection\ContainerInterface")->disableOriginalConstructor()->getMock();
	    $containerMock->expects($this->once())->method("get")->with("azine_mailgun.service")->will($this->returnValue($mailgunServiceMock));

	    $command->setContainer($containerMock);
	    $tester = new CommandTester($command);
	    $date = new \DateTime($dateString);
	    $mailgunServiceMock->expects($this->once())->method("removeEvents")->with($type, $date)->will($this->returnValue($count));
	    $tester->execute(array("date" => $dateString, "type" => $type));
	    $display = $tester->getDisplay();
	    $this->assertContains("All MailgunEvents (& their CustomVariables & Attachments) older than ".$date->format("Y-m-d H:i:s")." of type '$type' have been deleted ($count).", $display);
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
		$display = $tester->getDisplay();
		$this
				->assertContains(
						"All MailgunEvents (& their CustomVariables & Attachments) older than " . $date->format("Y-m-d H:i:s")
								. " of type '$type' have been deleted ($count).", $display);
	}
}

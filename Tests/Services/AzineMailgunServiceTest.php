<?php
namespace Azine\MailgunWebhooksBundle\Tests\Services;


/**
 * @author Dominik Businger
 */
use Azine\MailgunWebhooksBundle\Services\AzineMailgunService;

class AzineMailgunServiceTest extends \PHPUnit_Framework_TestCase {

	public function testRemoveOldEventEntries(){

		$ageLimit = new \DateTime("5 days ago");


		$q = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Tests\Services\AzineQueryMock")->disableOriginalConstructor()->getMock();
		$q->expects($this->once())->method("execute");

		$qb = $this->getMockBuilder("Doctrine\ORM\QueryBuilder")->disableOriginalConstructor()->getMock();
		$qb->expects($this->once())->method("delete")->will($this->returnValue($qb));
		$qb->expects($this->once())->method("from")->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", "e")->will($this->returnValue($qb));
		$qb->expects($this->once())->method("andWhere")->with("e.timestamp < :age")->will($this->returnValue($qb));
		$qb->expects($this->once())->method("setParameter")->with("age", $ageLimit->getTimestamp())->will($this->returnValue($qb));
		$qb->expects($this->once())->method("getQuery")->will($this->returnValue($q));

		$em = $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->getMock();
		$em->expects($this->once())->method("createQueryBuilder")->will($this->returnValue($qb));

		$amgs = new AzineMailgunService($em);
		$amgs->removeOldEventEntries($ageLimit);

	}

	public function testRemoveOldEventEntriesByType(){

		$ageLimit = new \DateTime("5 days ago");
		$type = "bounced";

		$q = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Tests\Services\AzineQueryMock")->disableOriginalConstructor()->getMock();
		$q->expects($this->once())->method("execute");

		$qb = $this->getMockBuilder("Doctrine\ORM\QueryBuilder")->disableOriginalConstructor()->getMock();
		$qb->expects($this->once())->method("delete")->will($this->returnValue($qb));
		$qb->expects($this->once())->method("from")->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", "e")->will($this->returnValue($qb));
		$qb->expects($this->exactly(2))->method("andWhere")->with()->will($this->returnValueMap(array(array("e.timestamp < :age", $qb), array("e.type = :type", $qb))));
		$qb->expects($this->exactly(2))->method("setParameter")->will($this->returnValueMap(array(array("age", $ageLimit->getTimestamp(), null, $qb), array("type", $type, null, $qb))));
		$qb->expects($this->once())->method("getQuery")->will($this->returnValue($q));

		$em = $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->getMock();
		$em->expects($this->once())->method("createQueryBuilder")->will($this->returnValue($qb));


		$amgs = new AzineMailgunService($em);
		$amgs->removeEvents($type, $ageLimit);

	}
}

<?php
namespace Azine\MailgunWebhooksBundle\Tests\Services;

/**
 * @author Dominik Businger
 */
use Azine\MailgunWebhooksBundle\Services\AzineMailgunService;

class AzineMailgunServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testRemoveOldEventEntries()
    {
        $ageLimit = new \DateTime("5 days ago");
        $count = 23;

        $q = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Tests\Services\AzineQueryMock")->disableOriginalConstructor()->getMock();
        $q->expects($this->once())->method("execute")->will($this->returnValue($count));

        $qb = $this->getMockBuilder("Doctrine\ORM\QueryBuilder")->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method("delete")->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", "e")->will($this->returnValue($qb));
        $qb->expects($this->once())->method("andWhere")->with("e.timestamp < :age")->will($this->returnValue($qb));
        $qb->expects($this->once())->method("setParameter")->with("age", $ageLimit->getTimestamp())->will($this->returnValue($qb));
        $qb->expects($this->once())->method("getQuery")->will($this->returnValue($q));

        $em = $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method("createQueryBuilder")->will($this->returnValue($qb));

        $registry = $this->getMockBuilder("Doctrine\Bundle\DoctrineBundle\Registry")->disableOriginalConstructor()->getMock();
        $registry->expects($this->once())->method("getManager")->will($this->returnValue($em));

        $amgs = new AzineMailgunService($registry);
        $deleteCount = $amgs->removeOldEventEntries($ageLimit);
        $this->assertEquals($count, $deleteCount, "Expected that $count entries are reported as deleted.");

    }

    public function testRemoveOldEventEntriesByType_with_string()
    {
        $ageLimit = new \DateTime("5 days ago");
        $type = "bounced";
        $count = 12;

        $q = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Tests\Services\AzineQueryMock")->disableOriginalConstructor()->getMock();
        $q->expects($this->once())->method("execute")->will($this->returnValue($count));

        $qb = $this->getMockBuilder("Doctrine\ORM\QueryBuilder")->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method("delete")->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", "e")->will($this->returnValue($qb));
        $qb->expects($this->exactly(2))->method("andWhere")->with()->will($this->returnValueMap(array(array("e.timestamp < :age", $qb), array("e.event = :type", $qb))));
        $qb->expects($this->exactly(2))->method("setParameter")->will($this->returnValueMap(array(array("age", $ageLimit->getTimestamp(), null, $qb), array("type", $type, null, $qb))));
        $qb->expects($this->once())->method("getQuery")->will($this->returnValue($q));

        $em = $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method("createQueryBuilder")->will($this->returnValue($qb));

        $registry = $this->getMockBuilder("Doctrine\Bundle\DoctrineBundle\Registry")->disableOriginalConstructor()->getMock();
        $registry->expects($this->once())->method("getManager")->will($this->returnValue($em));

        $amgs = new AzineMailgunService($registry);
        $deleteCount = $amgs->removeEvents($type, $ageLimit);
        $this->assertEquals($count, $deleteCount, "Expected that $count entries are reported as deleted.");

    }

    public function testRemoveOldEventEntriesByType_with_array()
    {
        $ageLimit = new \DateTime("5 days ago");
        $type = array("bounced","dropped");
        $count = 12;

        $q = $this->getMockBuilder("Azine\MailgunWebhooksBundle\Tests\Services\AzineQueryMock")->disableOriginalConstructor()->getMock();
        $q->expects($this->once())->method("execute")->will($this->returnValue($count));

        $qb = $this->getMockBuilder("Doctrine\ORM\QueryBuilder")->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method("delete")->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", "e")->will($this->returnValue($qb));
        $qb->expects($this->exactly(2))->method("andWhere")->with()->will($this->returnValueMap(array(array("e.timestamp < :age", $qb), array("e.event in (:type)", $qb))));
        $qb->expects($this->exactly(2))->method("setParameter")->will($this->returnValueMap(array(array("age", $ageLimit->getTimestamp(), null, $qb), array("type", $type, null, $qb))));
        $qb->expects($this->once())->method("getQuery")->will($this->returnValue($q));

        $em = $this->getMockBuilder("Doctrine\ORM\EntityManager")->disableOriginalConstructor()->getMock();
        $em->expects($this->once())->method("createQueryBuilder")->will($this->returnValue($qb));

        $registry = $this->getMockBuilder("Doctrine\Bundle\DoctrineBundle\Registry")->disableOriginalConstructor()->getMock();
        $registry->expects($this->once())->method("getManager")->will($this->returnValue($em));

        $amgs = new AzineMailgunService($registry);
        $deleteCount = $amgs->removeEvents($type, $ageLimit);
        $this->assertEquals($count, $deleteCount, "Expected that $count entries are reported as deleted.");

    }}

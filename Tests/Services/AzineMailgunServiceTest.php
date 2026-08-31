<?php

namespace Azine\MailgunWebhooksBundle\Tests\Services;

/*
 * @author Dominik Businger
 */
use Azine\MailgunWebhooksBundle\Services\AzineMailgunService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class AzineMailgunServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testRemoveOldEventEntries()
    {
        $ageLimit = new \DateTime('5 days ago');
        $count = 23;

        $q = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->onlyMethods(array('execute'))->getMock();
        $q->expects($this->once())->method('execute')->willReturn($count);

        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('delete')->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", 'e')->willReturn($qb);
        $qb->expects($this->once())->method('andWhere')->with('e.timestamp < :age')->willReturn($qb);
        $qb->expects($this->once())->method('setParameter')->with('age', $ageLimit->getTimestamp())->willReturn($qb);
        $qb->expects($this->once())->method('getQuery')->willReturn($q);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())->method('getManager')->willReturn($em);

        $amgs = new AzineMailgunService($registry);
        $deleteCount = $amgs->removeOldEventEntries($ageLimit);
        $this->assertSame($count, $deleteCount, "Expected that $count entries are reported as deleted.");
    }

    public function testRemoveOldEventEntriesByTypeWithString()
    {
        $ageLimit = new \DateTime('5 days ago');
        $type = 'bounced';
        $count = 12;

        $q = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->onlyMethods(array('execute'))->getMock();
        $q->expects($this->once())->method('execute')->willReturn($count);

        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('delete')->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", 'e')->willReturn($qb);
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnMap(array(array('e.timestamp < :age', $qb), array('e.event = :type', $qb)));
        $qb->expects($this->exactly(2))->method('setParameter')->willReturnMap(array(array('age', $ageLimit->getTimestamp(), null, $qb), array('type', $type, null, $qb)));
        $qb->expects($this->once())->method('getQuery')->willReturn($q);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())->method('getManager')->willReturn($em);

        $amgs = new AzineMailgunService($registry);
        $deleteCount = $amgs->removeEvents($type, $ageLimit);
        $this->assertSame($count, $deleteCount, "Expected that $count entries are reported as deleted.");
    }

    public function testRemoveOldEventEntriesByTypeWithArray()
    {
        $ageLimit = new \DateTime('5 days ago');
        $type = array('bounced', 'dropped');
        $count = 12;

        $q = $this->getMockBuilder(Query::class)->disableOriginalConstructor()->onlyMethods(array('execute'))->getMock();
        $q->expects($this->once())->method('execute')->willReturn($count);

        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())->method('delete')->with("Azine\MailgunWebhooksBundle\Entity\MailgunEvent", 'e')->willReturn($qb);
        $qb->expects($this->exactly(2))->method('andWhere')->willReturnMap(array(array('e.timestamp < :age', $qb), array('e.event in (:type)', $qb)));
        $qb->expects($this->exactly(2))->method('setParameter')->willReturnMap(array(array('age', $ageLimit->getTimestamp(), null, $qb), array('type', $type, null, $qb)));
        $qb->expects($this->once())->method('getQuery')->willReturn($q);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('createQueryBuilder')->willReturn($qb);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->once())->method('getManager')->willReturn($em);

        $amgs = new AzineMailgunService($registry);
        $deleteCount = $amgs->removeEvents($type, $ageLimit);
        $this->assertSame($count, $deleteCount, "Expected that $count entries are reported as deleted.");
    }
}

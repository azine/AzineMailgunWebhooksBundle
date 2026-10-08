<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Repository;

use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class EmailTrafficStatisticsRepositoryTest extends TestCase
{
    private EntityManager $manager;

    protected function setUp(): void
    {
        $configuration = ORMSetup::createConfiguration(true);
        $configuration->setMetadataDriverImpl(new SimplifiedXmlDriver([
            __DIR__.'/../../src/Resources/config/doctrine' => 'Azine\\MailgunWebhooksBundle\\Entity',
        ]));
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->manager = new EntityManager($connection, $configuration);
        (new SchemaTool($this->manager))->createSchema([
            $this->manager->getClassMetadata(EmailTrafficStatistics::class),
        ]);
    }

    protected function tearDown(): void
    {
        $this->manager->getConnection()->close();
        $this->manager->close();
    }

    public function testMissingActionReturnsNull(): void
    {
        self::assertNull($this->manager->getRepository(EmailTrafficStatistics::class)->getLastByAction('missing'));
    }

    public function testLatestMatchingAlertIsUsedForComplaintThrottling(): void
    {
        foreach ([
            ['spam_alert_sent', '2026-10-08 10:00:00'],
            ['spam_alert_sent', '2026-10-08 10:05:00'],
            ['another_action', '2026-10-08 10:10:00'],
        ] as [$action, $created]) {
            $record = (new EmailTrafficStatistics())->setAction($action)->setCreated(new \DateTime($created));
            $this->manager->persist($record);
        }
        $this->manager->flush();
        $this->manager->clear();
        $last = $this->manager->getRepository(EmailTrafficStatistics::class)->getLastByAction('spam_alert_sent');
        self::assertNotNull($last);
        self::assertSame('spam_alert_sent', $last->getAction());
        self::assertSame('2026-10-08 10:05:00', $last->getCreated()->format('Y-m-d H:i:s'));
    }
}

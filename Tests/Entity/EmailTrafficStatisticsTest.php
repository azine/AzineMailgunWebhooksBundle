<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Entity;

use Azine\MailgunWebhooksBundle\Entity\EmailTrafficStatistics;
use PHPUnit\Framework\TestCase;

final class EmailTrafficStatisticsTest extends TestCase
{
    public function testCreatedTimestampIsInitializedForNewEntity(): void
    {
        $before = new \DateTime();

        $statistics = new EmailTrafficStatistics();

        $after = new \DateTime();

        self::assertInstanceOf(\DateTime::class, $statistics->getCreated());
        self::assertGreaterThanOrEqual($before, $statistics->getCreated());
        self::assertLessThanOrEqual($after, $statistics->getCreated());
    }
}

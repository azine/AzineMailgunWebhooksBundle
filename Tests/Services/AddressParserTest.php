<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Services;

use Azine\MailgunWebhooksBundle\Services\AddressParser;
use PHPUnit\Framework\TestCase;

final class AddressParserTest extends TestCase
{
    public function testParsesMultipleAddressesAndQuotedDisplayNames(): void
    {
        $addresses = AddressParser::parse('"Doe, Jane" <jane@example.com>, John Doe <john@example.com>');

        self::assertCount(2, $addresses);
        self::assertSame('jane@example.com', $addresses[0]->getAddress());
        self::assertSame('Doe, Jane', $addresses[0]->getName());
        self::assertSame('john@example.com', $addresses[1]->getAddress());
        self::assertSame('John Doe', $addresses[1]->getName());
    }

    public function testInvalidFragmentsDoNotDiscardValidAddresses(): void
    {
        $addresses = AddressParser::parse('not-an-address, Valid Person <valid@example.com>');

        self::assertCount(1, $addresses);
        self::assertSame('valid@example.com', $addresses[0]->getAddress());
    }

    public function testCommaInsideAngleAddressIsNotUsedAsSeparator(): void
    {
        $addresses = AddressParser::parse('Example <"local,part"@example.com>');

        self::assertCount(1, $addresses);
        self::assertSame('"local,part"@example.com', $addresses[0]->getAddress());
    }
}

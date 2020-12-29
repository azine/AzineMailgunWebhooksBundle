<?php

namespace Azine\MailgunWebhooksBundle\Tests\Entity;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;

class MailgunEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Do not allow country or region to be set to 'Unknown'.
     */
    public function testCannotSetAsUnknown()
    {
        $event = new MailgunEvent();

        $event->setCountry('Unknown');
        $this->assertNull($event->getCountry());

        $event->setRegion('Unknown');
        $this->assertNull($event->getRegion());
    }

    /**
     * Test decoding of Json into messageHeaders property.
     *
     * @dataProvider messageHeadersJsonString
     *
     * @param string $jsonStringHeaders
     */
    public function testSetMessageHeaders($jsonStringHeaders)
    {
        // force strict error-checking. PHPunit will convert to exceptions
        $oldErrorlevel = error_reporting(-1);

        $event = new MailgunEvent();
        $actual = $event->setMessageHeaders($jsonStringHeaders);
        $this->assertSame($event, $actual);

        error_reporting($oldErrorlevel);
    }

    /**
     * Return sample JSON strings to be decoded.
     *
     * @return array one or more strings of JSON to parse
     */
    public function messageHeadersJsonString()
    {
        $tests[0][] = <<<'EOT'
{"geolocation":{"country":"Unknown","region":"Unknown","city":"Unknown"},
"tags":[],"ip":"12.123.12.12","log-level":"info","id":"_mGv6HusfhsdbjnR-wGGzx",
"campaigns":[],"user-variables":{},"recipient-domain":"example.com",
"timestamp":1506682072.767605,
"client-info":{"client-os":"Windows","device-type":"desktop","client-name":"Firefox",
"client-type":"browser",
"user-agent":"Mozilla/5.0 (Windows NT 5.1; rv:11.0) Gecko Firefox/11.0 (via ggpht.com GoogleImageProxy)"},
"message":{"headers":{
"message-id":"a560bc40c2af65952b566baf20cde2c1@test.test.tester.co.uk"}},
"recipient":"emailtest@example.com","event":"opened"}
EOT;

        return $tests;
    }
}

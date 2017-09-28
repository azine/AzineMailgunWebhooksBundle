<?php

namespace Azine\MailgunWebhooksBundle\Tests\Entity;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;

class MailgunEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Do not allow country or region to be set to 'Unknown'
     */
    public function testCannotSetAsUnknown()
    {
        $event = new MailgunEvent();

        $event->setCountry('Unknown');
        $this->assertNull($event->getCountry());

        $event->setRegion('Unknown');
        $this->assertNull($event->getRegion());
    }
}

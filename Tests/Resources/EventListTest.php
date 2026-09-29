<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Resources;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class EventListTest extends TestCase
{
    public function testListKeepsTheRowCompactAndLinksToFullEventDetails(): void
    {
        $event = new MailgunEvent();
        $event->setEvent('failed');
        $event->setTimestamp(1790000000);
        $event->setRecipient('tenant@example.com');
        $event->setDescription('Delivery failed');
        $event->setIp('203.0.113.12');
        $event->setMessageHeaders('{"Subject":"Check-in information"}');

        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2).'/src/Resources/views'), ['autoescape' => 'html']);
        $twig->addFunction(new TwigFunction('path', static fn (string $route, array $parameters = []): string => '/mailgun/event/'.$route));

        $html = $twig->render('MailgunEvent/eventList.html.twig', ['events' => [$event]]);

        self::assertSame(6, substr_count($html, '<th>'));
        self::assertStringContainsString('Check-in information', $html);
        self::assertStringContainsString('Delivery failed', $html);
        self::assertStringContainsString('View event', $html);
        self::assertStringNotContainsString('MessageHeaders', $html);
    }
}

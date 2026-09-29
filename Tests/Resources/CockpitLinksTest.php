<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class CockpitLinksTest extends TestCase
{
    public function testCockpitRendersCurrentDestinationsAndUsefulLookups(): void
    {
        $html = $this->twig()->render('cockpit.html.twig', [
            'emailDomain' => 'mg.example.com',
            'lastKnownIp' => '192.0.2.10',
        ]);

        self::assertStringContainsString('href="https://app.mailgun.com/app/dashboard"', $html);
        self::assertStringContainsString('href="https://app.mailgun.com/support"', $html);
        self::assertStringContainsString('href="https://app.mailgun.com/mg/sending/domains"', $html);
        self::assertStringContainsString('href="https://mail-tester.com/"', $html);
        self::assertStringContainsString('href="https://senderscore.org/assess/get-your-score/"', $html);
        self::assertStringContainsString('href="https://mxtoolbox.com/SuperTool.aspx?action=mx%3Amg.example.com&amp;run=toolpage"', $html);
        self::assertStringContainsString('href="https://hetrixtools.com/blacklist-check/192.0.2.10"', $html);
    }

    public function testMissingDomainAndIpRenderUsableToolLinks(): void
    {
        $html = $this->twig()->render('cockpit.html.twig', [
            'emailDomain' => '',
            'lastKnownIp' => '',
        ]);

        self::assertStringContainsString('href="https://mxtoolbox.com/SuperTool.aspx"', $html);
        self::assertStringContainsString('href="https://hetrixtools.com/blacklist-check/"', $html);
        self::assertStringContainsString('Email domain is not configured', $html);
        self::assertStringContainsString('No sending IP recorded', $html);
    }

    public function testExistingSupportTicketLinksToSupportWithTicketNumber(): void
    {
        $html = $this->notification('12345');

        self::assertStringContainsString('href="https://app.mailgun.com/support"', $html);
        self::assertStringContainsString('(12345)', $html);
        self::assertStringNotContainsString('/app/support/view/', $html);
    }

    public function testNewSupportTicketLinksToRequestForm(): void
    {
        $html = $this->notification(null);

        self::assertStringContainsString('href="https://help.mailgun.com/hc/en-us/requests/new"', $html);
        self::assertStringNotContainsString('/app/support/new', $html);
    }

    private function notification(?string $ticketId): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__, 2).'/src/Resources/views', 'AzineMailgunWebhooks');
        $twig = new Environment($loader, ['autoescape' => 'html']);
        $twig->addFilter(new TwigFilter('trans', static fn (string $key): string => $key));
        $twig->addFunction(new TwigFunction('url', static fn (string $route, array $parameters): string => 'https://example.com/mailgun/event/1/show'));

        return $twig->render('@AzineMailgunWebhooks/Email/notification.html.twig', [
            'eventId' => 1,
            'ticketId' => $ticketId,
        ]);
    }

    private function twig(): Environment
    {
        return new Environment(new FilesystemLoader(dirname(__DIR__, 2).'/src/Resources/views'), [
            'autoescape' => 'html',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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
        self::assertStringContainsString('Unable to get last sender IP', $html);
    }

    private function twig(): Environment
    {
        return new Environment(new FilesystemLoader(dirname(__DIR__, 2).'/src/Resources/views'), [
            'autoescape' => 'html',
        ]);
    }
}

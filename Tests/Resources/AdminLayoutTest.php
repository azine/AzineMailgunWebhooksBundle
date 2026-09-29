<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class AdminLayoutTest extends TestCase
{
    public function testStandaloneLayoutIncludesResponsiveStyles(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__, 2).'/src/Resources/views', 'AzineMailgunWebhooks');
        $twig = new Environment($loader, ['autoescape' => 'html']);
        $twig->addFunction(new TwigFunction('path', static fn (string $route): string => '/mailgun/event/delete'));

        $html = $twig->render('@AzineMailgunWebhooks/layout.html.twig');

        self::assertStringContainsString('<body class="mailgun-admin">', $html);
        self::assertStringContainsString('<meta name="viewport"', $html);
        self::assertStringContainsString('<style>', $html);
        self::assertStringContainsString('.mailgun-admin .mailgun-table-wrap', $html);
    }
}

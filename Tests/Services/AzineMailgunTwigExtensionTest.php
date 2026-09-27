<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Services;

use Azine\MailgunWebhooksBundle\Services\AzineMailgunTwigExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AzineMailgunTwigExtensionTest extends TestCase
{
    public function testRegistersTwig3Filter(): void
    {
        $filters = (new AzineMailgunTwigExtension())->getFilters();

        self::assertCount(1, $filters);
        self::assertInstanceOf(TwigFilter::class, $filters[0]);
        self::assertSame('printArray', $filters[0]->getName());
    }

    public function testPaginatorCompilesWithSupportedTwigSyntax(): void
    {
        $template = file_get_contents(dirname(__DIR__, 2).'/src/Resources/views/paginator.html.twig');
        self::assertIsString($template);

        $twig = new Environment(new ArrayLoader());
        $twig->addFunction(new TwigFunction('path', static fn (): string => '/'));
        $twig->createTemplate($template);

        self::addToAssertionCount(1);
    }
}

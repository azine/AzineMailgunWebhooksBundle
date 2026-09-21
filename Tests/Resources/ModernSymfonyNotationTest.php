<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;

final class ModernSymfonyNotationTest extends TestCase
{
    public function testBundleSourcesDoNotUseRemovedLegacyBundleShortcuts(): void
    {
        $root = dirname(__DIR__, 2).'/src';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        $violations = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || !preg_match('/\.(?:php|twig|ya?ml)$/', $file->getFilename())) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (false === $contents) {
                continue;
            }

            foreach ([
                'AzineMailgunWebhooksBundle::',
                'AzineMailgunWebhooksBundle:Mailgun:',
                'AzineMailgunWebhooksBundle:MailgunEvent:',
                'AzineMailgunWebhooksBundle:Email:',
            ] as $legacyNotation) {
                if (str_contains($contents, $legacyNotation)) {
                    $violations[] = str_replace($root.'/', '', $file->getPathname()).' => '.$legacyNotation;
                }
            }
        }

        self::assertSame([], $violations, "Legacy Symfony bundle notation remains:\n".implode("\n", $violations));
    }
}

<?php

declare(strict_types=1);

namespace Azine\MailgunWebhooksBundle\Services;

use Symfony\Component\Mime\Address;

/**
 * Parses RFC-style mailbox lists without requiring ext-mailparse.
 *
 * Symfony's Address parser handles one mailbox at a time. Mailgun payloads can
 * contain comma-separated mailbox lists, including quoted display names with
 * commas, so split the list first while respecting quotes and angle brackets.
 */
final class AddressParser
{
    /**
     * @return list<Address>
     */
    public static function parse(string $addressList): array
    {
        $addresses = [];

        foreach (self::split($addressList) as $mailbox) {
            try {
                $addresses[] = Address::create($mailbox);
            } catch (\InvalidArgumentException) {
                // Match the historical mailparse behaviour: malformed mailbox
                // fragments must not prevent otherwise valid addresses from
                // being processed.
            }
        }

        return $addresses;
    }

    /**
     * @return list<string>
     */
    private static function split(string $addressList): array
    {
        $parts = [];
        $buffer = '';
        $quoted = false;
        $escaped = false;
        $angleDepth = 0;
        $length = strlen($addressList);

        for ($i = 0; $i < $length; ++$i) {
            $char = $addressList[$i];

            if ($escaped) {
                $buffer .= $char;
                $escaped = false;

                continue;
            }

            if ($quoted && '\\' === $char) {
                $buffer .= $char;
                $escaped = true;

                continue;
            }

            if ('"' === $char) {
                $quoted = !$quoted;
                $buffer .= $char;

                continue;
            }

            if (!$quoted) {
                if ('<' === $char) {
                    ++$angleDepth;
                } elseif ('>' === $char && $angleDepth > 0) {
                    --$angleDepth;
                } elseif (',' === $char && 0 === $angleDepth) {
                    self::appendPart($parts, $buffer);
                    $buffer = '';

                    continue;
                }
            }

            $buffer .= $char;
        }

        self::appendPart($parts, $buffer);

        return $parts;
    }

    /**
     * @param list<string> $parts
     */
    private static function appendPart(array &$parts, string $value): void
    {
        $value = trim($value);
        if ('' !== $value) {
            $parts[] = $value;
        }
    }
}

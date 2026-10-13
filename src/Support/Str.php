<?php

declare(strict_types=1);

namespace Forge\Archive\Support;

use Symfony\Component\String\UnicodeString;

use function Symfony\Component\String\u;

final class Str
{
    private function __construct()
    {
    }

    /**
     * @param array<string, string|int> $values
     *
     * @return string
     */
    public static function format(string $template, array $values): string
    {
        return strtr($template, array_combine(
            array_map(self::placeholder(...), array_keys($values)),
            array_map(static fn (string|int $value): string => (string) $value, array_values($values)),
        ));
    }

    /**
     * @return string
     */
    private static function placeholder(string $key): string
    {
        return u()->join([UnicodeString::fromCodePoints(40)->toString(), $key, UnicodeString::fromCodePoints(41)->toString()])->toString();
    }
}

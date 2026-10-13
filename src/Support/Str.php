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
        $text = u($template);

        foreach ($values as $key => $value) {
            $text = $text->replace(self::placeholder($key), (string) $value);
        }

        return $text->toString();
    }

    /**
     * @return string
     */
    private static function placeholder(string $key): string
    {
        return UnicodeString::fromCodePoints(40)->append($key, UnicodeString::fromCodePoints(41)->toString())->toString();
    }
}

<?php

declare(strict_types=1);

namespace Forge\Archive;

use Forge\Archive\Exception\UnsupportedFormatException;
use Symfony\Component\String\UnicodeString;

use function Symfony\Component\String\u;

enum Format: string
{
    case Zip = 'zip';
    case Tar = 'tar';
    case TarGz = 'tar.gz';
    case TarXz = 'tar.xz';
    case TarBz2 = 'tar.bz2';

    /**
     * @throws UnsupportedFormatException
     */
    public static function detect(string $file): self
    {
        $format = array_find(self::cases(), static fn (self $format): bool => $format->matches($file));

        if ($format instanceof self) {
            return $format;
        }

        throw UnsupportedFormatException::fromFile($file);
    }

    /**
     * @return bool
     */
    public function matches(string $file): bool
    {
        return u($file)->ignoreCase()->endsWith($this->extension());
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return u($this->value)->ensureStart(UnicodeString::fromCodePoints(46)->toString())->toString();
    }
}

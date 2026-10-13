<?php

declare(strict_types=1);

namespace Forge\Archive\Tests;

use Forge\Archive\Exception\UnsupportedFormatException;
use Forge\Archive\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormatTest extends TestCase
{
    /**
     * @return iterable<string, array{string, Format}>
     */
    public static function files(): iterable
    {
        yield 'zip' => ['release.zip', Format::Zip];
        yield 'tar' => ['release.tar', Format::Tar];
        yield 'tar.gz' => ['release.tar.gz', Format::TarGz];
        yield 'tar.xz' => ['release.tar.xz', Format::TarXz];
        yield 'tar.bz2' => ['release.tar.bz2', Format::TarBz2];
        yield 'uppercase' => ['RELEASE.TAR.GZ', Format::TarGz];
    }

    #[DataProvider('files')]
    public function testDetectsTheFormatFromTheExtension(string $file, Format $format): void
    {
        $this->assertSame($format, Format::detect($file));
    }

    public function testExtensionStartsWithADot(): void
    {
        $this->assertSame('.tar.gz', Format::TarGz->extension());
    }

    public function testRejectsUnknownExtensions(): void
    {
        $this->expectExceptionObject(UnsupportedFormatException::fromFile('release.rar'));

        Format::detect('release.rar');
    }
}

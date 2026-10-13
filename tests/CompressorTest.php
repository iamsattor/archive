<?php

declare(strict_types=1);

namespace Forge\Archive\Tests;

use Forge\Archive\Compressor;
use Forge\Archive\Exception\SourceNotFoundException;
use Forge\Archive\Format;
use Forge\Archive\Inspector;
use Forge\Archive\Tests\Fixture\FilesystemTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Path;

use function Symfony\Component\String\u;

final class CompressorTest extends FilesystemTestCase
{
    /**
     * @return iterable<string, array{Format}>
     */
    public static function formats(): iterable
    {
        yield 'zip' => [Format::Zip];
        yield 'tar.gz' => [Format::TarGz];
    }

    #[DataProvider('formats')]
    public function testArchivesASingleFileWithoutItsParent(Format $format): void
    {
        $file = $this->write('content');
        $archive = $this->file();

        new Compressor()->create($file, $archive, $format);

        $this->assertSame([basename($file)], new Inspector()->entries($archive, $format));
    }

    #[DataProvider('formats')]
    public function testTreatsOptionLikeNamesAsFiles(Format $format): void
    {
        $file = Path::join($this->directory(), u()->join([$this->flag(45, 45), 'use-compress-program=', $this->random()])->toString());
        $archive = $this->file();

        $this->filesystem->dumpFile($file, 'content');

        new Compressor()->create($file, $archive, $format);

        $this->assertSame([basename($file)], new Inspector()->entries($archive, $format));
    }

    public function testCreatesTheArchiveDirectory(): void
    {
        $archive = Path::join($this->file(), $this->random());

        new Compressor()->create($this->write('content'), $archive, Format::TarGz);

        $this->assertFileExists($archive);
    }

    public function testRejectsAMissingSource(): void
    {
        $source = $this->file();

        $this->expectExceptionObject(SourceNotFoundException::fromPath($source));

        new Compressor()->create($source, $this->file(), Format::Zip);
    }
}

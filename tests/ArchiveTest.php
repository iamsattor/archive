<?php

declare(strict_types=1);

namespace Forge\Archive\Tests;

use Forge\Archive\Archive;
use Forge\Archive\Format;
use Forge\Archive\Tests\Fixture\FilesystemTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Filesystem\Path;

final class ArchiveTest extends FilesystemTestCase
{
    /**
     * @return iterable<string, array{Format}>
     */
    public static function formats(): iterable
    {
        foreach (Format::cases() as $format) {
            yield $format->value => [$format];
        }
    }

    #[DataProvider('formats')]
    public function testCreatesAndExtractsEveryFormat(Format $format): void
    {
        $source = $this->directory();
        $name = $this->random();
        $archive = $this->file();
        $destination = $this->file();

        $this->filesystem->dumpFile(Path::join($source, $name), 'content');

        new Archive()->create($source, $archive, $format);
        new Archive()->extract($archive, $destination, $format);

        $this->assertStringEqualsFile(Path::join($destination, basename($source), $name), 'content');
        $this->assertContains(Path::join(basename($source), $name), new Archive()->entries($archive, $format));
    }
}

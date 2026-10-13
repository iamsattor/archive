<?php

declare(strict_types=1);

namespace Forge\Archive\Tests;

use Forge\Archive\Exception\ArchiveTooLargeException;
use Forge\Archive\Exception\UnsafeEntryException;
use Forge\Archive\Extractor;
use Forge\Archive\Format;
use Forge\Archive\Support\Str;
use Forge\Archive\Tests\Fixture\FilesystemTestCase;
use Symfony\Component\Filesystem\Path;

final class ExtractorTest extends FilesystemTestCase
{
    private string $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destination = Path::join($this->directory(), $this->random());
    }

    public function testMergesIntoAnExistingDestination(): void
    {
        $kept = $this->random();
        $replaced = $this->random();
        $archive = $this->tar($this->mtree($this->entry($replaced, $this->write('new'))));

        $this->filesystem->dumpFile(Path::join($this->destination, $kept), 'kept');
        $this->filesystem->dumpFile(Path::join($this->destination, $replaced), 'old');

        new Extractor()->extract($archive, $this->destination, Format::Tar);

        $this->assertStringEqualsFile(Path::join($this->destination, $kept), 'kept');
        $this->assertStringEqualsFile(Path::join($this->destination, $replaced), 'new');
    }

    public function testRejectsEntriesOutsideTheDestination(): void
    {
        $escape = Path::join(Path::getDirectory($this->destination), $this->random());
        $entry = Path::makeRelative($escape, $this->destination);
        $archive = $this->tar($this->mtree($this->entry($entry, $this->write('payload'))));

        $this->assertRejected(UnsafeEntryException::fromEntry($archive, $entry), $archive);
        $this->assertFileDoesNotExist($escape);
    }

    public function testRejectsAbsoluteEntries(): void
    {
        $payload = $this->write('payload');
        $archive = $this->tar($payload);

        $this->assertRejected(UnsafeEntryException::fromEntry($archive, $payload), $archive);
    }

    public function testRejectsAbsoluteLinks(): void
    {
        $link = $this->random();
        $target = $this->directory();
        $archive = $this->tar($this->mtree($this->link($link, $target)));

        $this->assertRejected(UnsafeEntryException::fromLink($archive, Str::format('./(link)', ['link' => $link]), $target), $archive);
    }

    public function testRejectsRelativeLinksOutsideTheDestination(): void
    {
        $link = $this->random();
        $target = Path::makeRelative($this->directory(), $this->destination);
        $archive = $this->tar($this->mtree($this->link($link, $target)));

        $this->assertRejected(UnsafeEntryException::fromLink($archive, Str::format('./(link)', ['link' => $link]), $target), $archive);
    }

    public function testRejectsSpecialFiles(): void
    {
        $fifo = $this->random();
        $archive = $this->tar($this->mtree(Str::format('(fifo) type=fifo', ['fifo' => $fifo])));

        $this->assertRejected(UnsafeEntryException::fromType($archive, Str::format('./(fifo)', ['fifo' => $fifo])), $archive);
    }

    public function testRejectsArchivesAboveTheLimit(): void
    {
        $archive = $this->tar($this->mtree($this->entry($this->random(), $this->write('payload'))));

        $this->expectExceptionObject(ArchiveTooLargeException::fromFile($archive, 1));

        new Extractor(limit: 1)->extract($archive, $this->destination, Format::Tar);
    }

    public function testRestoresOwnerPermissionsAndKeepsInternalLinks(): void
    {
        $locked = $this->random();
        $link = $this->random();
        $archive = $this->tar($this->mtree(
            Str::format('(file) type=file mode=0000 contents=(contents)', ['file' => $locked, 'contents' => $this->write('secret')]),
            $this->link($link, $locked),
        ));

        new Extractor()->extract($archive, $this->destination, Format::Tar);

        $this->assertStringEqualsFile(Path::join($this->destination, $locked), 'secret');
        $this->assertStringEqualsFile(Path::join($this->destination, $link), 'secret');
        $this->assertTrue(is_writable(Path::join($this->destination, $locked)));
    }

    private function assertRejected(UnsafeEntryException $expected, string $archive): void
    {
        try {
            new Extractor()->extract($archive, $this->destination, Format::Tar);

            $this->fail('The unsafe archive was extracted.');
        } catch (UnsafeEntryException $exception) {
            $this->assertSame($expected->getMessage(), $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->destination);
    }

    private function entry(string $name, string $contents): string
    {
        return Str::format('(name) type=file contents=(contents)', ['name' => $name, 'contents' => $contents]);
    }

    private function link(string $name, string $target): string
    {
        return Str::format('(name) type=link link=(target)', ['name' => $name, 'target' => $target]);
    }
}

<?php

declare(strict_types=1);

namespace Forge\Archive\Tests;

use Forge\Archive\Exception\ArchiveNotFoundException;
use Forge\Archive\Exception\InspectionFailedException;
use Forge\Archive\Exception\UnsupportedFormatException;
use Forge\Archive\Format;
use Forge\Archive\Inspector;
use Forge\Archive\Node;
use Forge\Archive\NodeType;
use Forge\Archive\Support\Str;
use Forge\Archive\Tests\Fixture\FilesystemTestCase;

final class InspectorTest extends FilesystemTestCase
{
    public function testDescribesEveryNode(): void
    {
        $file = $this->random();
        $link = $this->random();
        $archive = $this->tar($this->mtree(
            Str::format('(file) type=file contents=(contents)', ['file' => $file, 'contents' => $this->write('four')]),
            Str::format('(link) type=link link=(file)', ['link' => $link, 'file' => $file]),
        ));

        $nodes = new Inspector()->nodes($archive, Format::Tar);

        $this->assertContainsEquals(new Node(Str::format('./(file)', ['file' => $file]), NodeType::File, null, 4), $nodes);
        $this->assertContainsEquals(new Node(Str::format('./(link)', ['link' => $link]), NodeType::Link, $file, 0), $nodes);
        $this->assertEqualsCanonicalizing([$file, $link], new Inspector()->entries($archive, Format::Tar));
    }

    public function testMarksUnsupportedTypes(): void
    {
        $fifo = $this->random();
        $archive = $this->tar($this->mtree(Str::format('(fifo) type=fifo', ['fifo' => $fifo])));

        $this->assertEquals([new Node(Str::format('./(fifo)', ['fifo' => $fifo]), null, null, 0)], new Inspector()->nodes($archive, Format::Tar));
    }

    public function testRejectsAMissingArchive(): void
    {
        $archive = $this->file();

        $this->expectExceptionObject(ArchiveNotFoundException::fromFile($archive));

        new Inspector()->entries($archive);
    }

    public function testRequiresAFormatForUnknownExtensions(): void
    {
        $archive = $this->tar($this->write('content'));

        $this->assertCount(1, new Inspector()->entries($archive, Format::Tar));

        $this->expectExceptionObject(UnsupportedFormatException::fromFile($archive));

        new Inspector()->entries($archive);
    }

    public function testReportsUnreadableArchives(): void
    {
        $archive = $this->write('garbage');

        $this->expectException(InspectionFailedException::class);
        $this->expectExceptionMessage(Str::format('Unable to read (file).', ['file' => $archive]));

        new Inspector()->entries($archive, Format::TarGz);
    }
}

<?php

declare(strict_types=1);

namespace Forge\Archive;

use Forge\Archive\Exception\ArchiveNotFoundException;
use Forge\Archive\Exception\ArchiveTooLargeException;
use Forge\Archive\Exception\CompressionFailedException;
use Forge\Archive\Exception\ExtractionFailedException;
use Forge\Archive\Exception\InspectionFailedException;
use Forge\Archive\Exception\SourceNotFoundException;
use Forge\Archive\Exception\UnsafeEntryException;
use Forge\Archive\Exception\UnsupportedFormatException;

final readonly class Archive
{
    public function __construct(
        private Extractor $extractor = new Extractor(),
        private Compressor $compressor = new Compressor(),
        private Inspector $inspector = new Inspector(),
    ) {
    }

    /**
     * @throws ArchiveNotFoundException
     * @throws UnsupportedFormatException
     * @throws InspectionFailedException
     * @throws UnsafeEntryException
     * @throws ArchiveTooLargeException
     * @throws ExtractionFailedException
     * @throws \LogicException
     */
    public function extract(string $archive, string $destination, ?Format $format = null): void
    {
        $this->extractor->extract($archive, $destination, $format);
    }

    /**
     * @throws SourceNotFoundException
     * @throws CompressionFailedException
     * @throws \LogicException
     */
    public function create(string $source, string $archive, Format $format): void
    {
        $this->compressor->create($source, $archive, $format);
    }

    /**
     * @return list<string>
     *
     * @throws ArchiveNotFoundException
     * @throws UnsupportedFormatException
     * @throws InspectionFailedException
     */
    public function entries(string $archive, ?Format $format = null): array
    {
        return $this->inspector->entries($archive, $format);
    }

    /**
     * @return list<Node>
     *
     * @throws ArchiveNotFoundException
     * @throws UnsupportedFormatException
     * @throws InspectionFailedException
     */
    public function nodes(string $archive, ?Format $format = null): array
    {
        return $this->inspector->nodes($archive, $format);
    }
}

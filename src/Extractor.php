<?php

declare(strict_types=1);

namespace Forge\Archive;

use Forge\Archive\Exception\ArchiveNotFoundException;
use Forge\Archive\Exception\ArchiveTooLargeException;
use Forge\Archive\Exception\ExtractionFailedException;
use Forge\Archive\Exception\InspectionFailedException;
use Forge\Archive\Exception\UnsafeEntryException;
use Forge\Archive\Exception\UnsupportedFormatException;
use Forge\Archive\Support\WorkingDirectory;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\String\UnicodeString;

final readonly class Extractor
{
    /**
     * @param int<1, max> $limit
     */
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
        private Inspector $inspector = new Inspector(),
        private int $limit = 8 * 1024 ** 3,
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
        $root = Path::makeAbsolute($destination, WorkingDirectory::path());
        $nodes = $this->inspector->nodes($archive, $format);

        $this->entries($archive, $root, $format);
        $this->types($archive, $nodes);
        $this->links($archive, $root, $nodes);
        $this->size($archive, $nodes);

        $this->run($archive, $root);
    }

    /**
     * @throws ArchiveNotFoundException
     * @throws UnsupportedFormatException
     * @throws InspectionFailedException
     * @throws UnsafeEntryException
     */
    private function entries(string $archive, string $root, ?Format $format): void
    {
        $entry = array_find($this->inspector->entries($archive, $format), fn (string $entry): bool => $this->escapes($entry, $root, $root));

        if (\is_string($entry)) {
            throw UnsafeEntryException::fromEntry($archive, $entry);
        }
    }

    /**
     * @param list<Node> $nodes
     *
     * @throws UnsafeEntryException
     */
    private function types(string $archive, array $nodes): void
    {
        $node = array_find($nodes, static fn (Node $node): bool => null === $node->type);

        if ($node instanceof Node) {
            throw UnsafeEntryException::fromType($archive, $node->path);
        }
    }

    /**
     * @param list<Node> $nodes
     *
     * @throws UnsafeEntryException
     */
    private function links(string $archive, string $root, array $nodes): void
    {
        $node = array_find($nodes, fn (Node $node): bool => $this->dangerous($node, $root));

        if ($node instanceof Node && \is_string($node->target)) {
            throw UnsafeEntryException::fromLink($archive, $node->path, $node->target);
        }
    }

    /**
     * @param list<Node> $nodes
     *
     * @throws ArchiveTooLargeException
     */
    private function size(string $archive, array $nodes): void
    {
        if (array_sum(array_column($nodes, 'size')) > $this->limit) {
            throw ArchiveTooLargeException::fromFile($archive, $this->limit);
        }
    }

    /**
     * @return bool
     */
    private function dangerous(Node $node, string $root): bool
    {
        if (NodeType::Link === $node->type && \is_string($node->target)) {
            return $this->escapes($node->target, Path::getDirectory(Path::makeAbsolute($node->path, $root)), $root);
        }

        return false;
    }

    /**
     * @return bool
     */
    private function escapes(string $path, string $base, string $root): bool
    {
        if (Path::isBasePath($root, Path::makeAbsolute($path, $base))) {
            return false;
        }

        return true;
    }

    /**
     * @throws ExtractionFailedException
     */
    private function run(string $archive, string $root): void
    {
        $staging = Path::join(Path::getDirectory($root), bin2hex(random_bytes(16)));

        try {
            $this->filesystem->mkdir($staging);

            new Process([Tool::Tar->value, $this->flag(45, 120, 102), $archive, $this->flag(45, 67), $staging], timeout: null)->mustRun();
            new Process([Tool::Chmod->value, $this->flag(45, 82), $this->flag(117, 43, 114, 119, 88), $staging], timeout: null)->mustRun();

            $this->publish($staging, $root);
        } catch (IOException|ProcessFailedException $exception) {
            throw ExtractionFailedException::fromFile($archive, $exception);
        } finally {
            $this->filesystem->remove($staging);
        }
    }

    /**
     * @throws IOException
     */
    private function publish(string $staging, string $root): void
    {
        if ($this->filesystem->exists($root)) {
            $this->filesystem->mirror($staging, $root, options: ['override' => true]);

            return;
        }

        $this->filesystem->rename($staging, $root);
    }

    /**
     * @return string
     */
    private function flag(int ...$codePoints): string
    {
        return UnicodeString::fromCodePoints(...$codePoints)->toString();
    }
}

<?php

declare(strict_types=1);

namespace Forge\Archive;

use Forge\Archive\Exception\CompressionFailedException;
use Forge\Archive\Exception\SourceNotFoundException;
use Forge\Archive\Support\WorkingDirectory;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\String\UnicodeString;

final readonly class Compressor
{
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * @throws SourceNotFoundException
     * @throws CompressionFailedException
     * @throws \LogicException
     */
    public function create(string $source, string $archive, Format $format): void
    {
        $path = realpath($source);

        if (\is_string($path)) {
            $this->run($path, Path::makeAbsolute($archive, WorkingDirectory::path()), $format);

            return;
        }

        throw SourceNotFoundException::fromPath($source);
    }

    /**
     * @throws CompressionFailedException
     */
    private function run(string $source, string $archive, Format $format): void
    {
        try {
            $this->filesystem->mkdir(Path::getDirectory($archive));

            new Process($this->command($source, $archive, $format), Path::getDirectory($source), timeout: null)->mustRun();
        } catch (IOException|ProcessFailedException $exception) {
            throw CompressionFailedException::fromFile($archive, $exception);
        }
    }

    /**
     * @return list<string>
     */
    private function command(string $source, string $archive, Format $format): array
    {
        return match ($format) {
            Format::Zip => [Tool::Ditto->value, ...$this->zip($source), $source, $archive],
            Format::Tar => $this->tar($this->flag(45, 99, 102), $source, $archive),
            Format::TarGz => $this->tar($this->flag(45, 99, 122, 102), $source, $archive),
            Format::TarXz => $this->tar($this->flag(45, 99, 74, 102), $source, $archive),
            Format::TarBz2 => $this->tar($this->flag(45, 99, 106, 102), $source, $archive),
        };
    }

    /**
     * @return list<string>
     */
    private function tar(string $mode, string $source, string $archive): array
    {
        return [
            Tool::Tar->value,
            $this->flag(45, 45, 110, 111, 45, 109, 97, 99, 45, 109, 101, 116, 97, 100, 97, 116, 97),
            $this->flag(45, 45, 110, 111, 45, 120, 97, 116, 116, 114, 115),
            $mode,
            $archive,
            $this->flag(45, 45),
            basename($source),
        ];
    }

    /**
     * @return list<string>
     */
    private function zip(string $source): array
    {
        $flags = [
            $this->flag(45, 99),
            $this->flag(45, 107),
            $this->flag(45, 45, 110, 111, 114, 115, 114, 99),
            $this->flag(45, 45, 110, 111, 101, 120, 116, 97, 116, 116, 114),
            $this->flag(45, 45, 110, 111, 113, 116, 110),
        ];

        if (is_dir($source)) {
            return [...$flags, $this->flag(45, 45, 107, 101, 101, 112, 80, 97, 114, 101, 110, 116)];
        }

        return $flags;
    }

    /**
     * @return string
     */
    private function flag(int ...$codePoints): string
    {
        return UnicodeString::fromCodePoints(...$codePoints)->toString();
    }
}

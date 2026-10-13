<?php

declare(strict_types=1);

namespace Forge\Archive;

use Forge\Archive\Exception\ArchiveNotFoundException;
use Forge\Archive\Exception\InspectionFailedException;
use Forge\Archive\Exception\UnsupportedFormatException;
use Forge\Archive\Support\Str;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\String\AbstractString;
use Symfony\Component\String\UnicodeString;

use function Symfony\Component\String\u;

final readonly class Inspector
{
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
    ) {
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
        $this->validate($archive, $format);

        return $this->lines($this->run($archive, [Tool::Tar->value, $this->flag(45, 116, 102), $archive]));
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
        $this->validate($archive, $format);

        return array_values(array_filter(
            array_map($this->node(...), $this->lines($this->run($archive, $this->manifest($archive)))),
            static fn (?Node $node): bool => $node instanceof Node,
        ));
    }

    /**
     * @throws ArchiveNotFoundException
     * @throws UnsupportedFormatException
     */
    private function validate(string $archive, ?Format $format): void
    {
        if ($this->filesystem->exists($archive)) {
            $this->support($archive, $format);

            return;
        }

        throw ArchiveNotFoundException::fromFile($archive);
    }

    /**
     * @throws UnsupportedFormatException
     */
    private function support(string $archive, ?Format $format): void
    {
        if (null === $format) {
            Format::detect($archive);
        }
    }

    /**
     * @param list<string> $command
     *
     * @return string
     *
     * @throws InspectionFailedException
     */
    private function run(string $archive, array $command): string
    {
        try {
            return new Process($command, timeout: null)->mustRun()->getOutput();
        } catch (ProcessFailedException $exception) {
            throw InspectionFailedException::fromFile($archive, $exception);
        }
    }

    /**
     * @return list<string>
     */
    private function manifest(string $archive): array
    {
        return [
            Tool::Tar->value,
            $this->flag(45, 99, 102),
            $this->flag(45),
            $this->flag(45, 45, 102, 111, 114, 109, 97, 116, 61, 109, 116, 114, 101, 101),
            $this->flag(45, 45, 111, 112, 116, 105, 111, 110, 115, 61, 33, 97, 108, 108, 44, 116, 121, 112, 101, 44, 108, 105, 110, 107, 44, 115, 105, 122, 101),
            Str::format('(source)(archive)', ['source' => $this->flag(64), 'archive' => $archive]),
        ];
    }

    /**
     * @return Node|null
     */
    private function node(string $line): ?Node
    {
        $tokens = u($line)->split($this->flag(32));
        $path = array_first($tokens);
        $type = $this->value($tokens, $this->flag(116, 121, 112, 101, 61));

        if ($path instanceof AbstractString && \is_string($type)) {
            return new Node(
                stripcslashes($path->toString()),
                NodeType::tryFrom($type),
                $this->value($tokens, $this->flag(108, 105, 110, 107, 61)),
                max(0, (int) $this->value($tokens, $this->flag(115, 105, 122, 101, 61))),
            );
        }

        return null;
    }

    /**
     * @param array<AbstractString> $tokens
     *
     * @return string|null
     */
    private function value(array $tokens, string $keyword): ?string
    {
        $token = array_find($tokens, static fn (AbstractString $token): bool => $token->startsWith($keyword));

        if ($token instanceof AbstractString) {
            return stripcslashes($token->after($keyword)->toString());
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function lines(string $output): array
    {
        return array_values(array_map(
            static fn (AbstractString $line): string => $line->trim()->toString(),
            array_filter(u($output)->split(\PHP_EOL), static fn (AbstractString $line): bool => $line->trim()->length() > 0),
        ));
    }

    /**
     * @return string
     */
    private function flag(int ...$codePoints): string
    {
        return UnicodeString::fromCodePoints(...$codePoints)->toString();
    }
}

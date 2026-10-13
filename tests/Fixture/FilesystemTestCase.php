<?php

declare(strict_types=1);

namespace Forge\Archive\Tests\Fixture;

use Forge\Archive\Tool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use Symfony\Component\String\UnicodeString;

use function Symfony\Component\String\u;

abstract class FilesystemTestCase extends TestCase
{
    protected Filesystem $filesystem;

    private string $root;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = $this->real(Path::join(sys_get_temp_dir(), $this->random()));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    /**
     * @return string
     */
    protected function random(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * @return string
     */
    protected function directory(): string
    {
        return $this->real(Path::join($this->root, $this->random()));
    }

    /**
     * @return string
     */
    protected function file(): string
    {
        return Path::join($this->root, $this->random());
    }

    /**
     * @return string
     */
    protected function write(string $content): string
    {
        $file = $this->file();

        $this->filesystem->dumpFile($file, $content);

        return $file;
    }

    /**
     * @return string
     */
    protected function tar(string ...$operands): string
    {
        $archive = $this->file();

        new Process([Tool::Tar->value, $this->flag(45, 80), $this->flag(45, 99, 102), $archive, ...$operands], $this->root)->mustRun();

        return $archive;
    }

    /**
     * @return string
     */
    protected function mtree(string ...$lines): string
    {
        $specification = $this->file();

        $this->filesystem->dumpFile($specification, u(\PHP_EOL)->join([$this->flag(35, 109, 116, 114, 101, 101), ...$lines, ''])->toString());

        return u()->join([$this->flag(64), $specification])->toString();
    }

    /**
     * @return string
     */
    protected function flag(int ...$codePoints): string
    {
        return UnicodeString::fromCodePoints(...$codePoints)->toString();
    }

    /**
     * @return string
     *
     * @throws \LogicException
     */
    private function real(string $directory): string
    {
        $this->filesystem->mkdir($directory);

        $path = $this->filesystem->readlink($directory, true);

        if (\is_string($path)) {
            return $path;
        }

        throw new \LogicException('The directory is not available.');
    }
}

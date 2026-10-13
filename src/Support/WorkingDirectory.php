<?php

declare(strict_types=1);

namespace Forge\Archive\Support;

final class WorkingDirectory
{
    private function __construct()
    {
    }

    /**
     * @return string
     *
     * @throws \LogicException
     */
    public static function path(): string
    {
        $directory = getcwd();

        if (\is_string($directory)) {
            return $directory;
        }

        throw new \LogicException('Working directory is not available.');
    }
}

<?php

declare(strict_types=1);

namespace Forge\Archive\Exception;

use Forge\Archive\Support\Str;

final class SourceNotFoundException extends ArchiveException
{
    /**
     * @return self
     */
    public static function fromPath(string $path): self
    {
        return new self(Str::format('Source (path) not found.', ['path' => $path]));
    }
}

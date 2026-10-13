<?php

declare(strict_types=1);

namespace Forge\Archive\Exception;

use Forge\Archive\Support\Str;

final class UnsupportedFormatException extends ArchiveException
{
    /**
     * @return self
     */
    public static function fromFile(string $file): self
    {
        return new self(Str::format('Unsupported archive format for (file).', ['file' => $file]));
    }
}

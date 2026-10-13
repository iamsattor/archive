<?php

declare(strict_types=1);

namespace Forge\Archive\Exception;

use Forge\Archive\Support\Str;

final class CompressionFailedException extends ArchiveException
{
    /**
     * @return self
     */
    public static function fromFile(string $file, \Throwable $previous): self
    {
        return new self(Str::format('Unable to create (file).', ['file' => $file]), 0, $previous);
    }
}

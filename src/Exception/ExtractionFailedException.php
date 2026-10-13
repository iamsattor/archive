<?php

declare(strict_types=1);

namespace Forge\Archive\Exception;

use Forge\Archive\Support\Str;

final class ExtractionFailedException extends ArchiveException
{
    /**
     * @return self
     */
    public static function fromFile(string $file, \Throwable $previous): self
    {
        return new self(Str::format('Unable to extract (file).', ['file' => $file]), 0, $previous);
    }
}

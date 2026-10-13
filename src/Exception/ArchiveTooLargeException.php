<?php

declare(strict_types=1);

namespace Forge\Archive\Exception;

use Forge\Archive\Support\Str;

final class ArchiveTooLargeException extends ArchiveException
{
    /**
     * @return self
     */
    public static function fromFile(string $file, int $limit): self
    {
        return new self(Str::format('Archive (file) exceeds the size limit of (limit) bytes.', ['file' => $file, 'limit' => $limit]));
    }
}

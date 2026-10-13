<?php

declare(strict_types=1);

namespace Forge\Archive;

final readonly class Node
{
    /**
     * @param int<0, max> $size
     */
    public function __construct(
        public string $path,
        public ?NodeType $type,
        public ?string $target,
        public int $size,
    ) {
    }
}

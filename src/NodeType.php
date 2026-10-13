<?php

declare(strict_types=1);

namespace Forge\Archive;

enum NodeType: string
{
    case File = 'file';
    case Directory = 'dir';
    case Link = 'link';
}

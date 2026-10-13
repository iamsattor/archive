<?php

declare(strict_types=1);

namespace Forge\Archive;

enum Tool: string
{
    case Tar = '/usr/bin/tar';
    case Ditto = '/usr/bin/ditto';
    case Chmod = '/bin/chmod';
}

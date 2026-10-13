# Archive

Safe archive extraction and creation for macOS, built on the tools that ship with every Mac.

```php
use Forge\Archive\Archive;
use Forge\Archive\Format;

$archive = new Archive();

$archive->extract('node-v24.tar.gz', '/opt/node');
$archive->create('project', 'project.zip', Format::Zip);
$archive->entries('project.zip');
```

## Requirements

- macOS (Apple silicon or Intel)
- PHP 8.5 or newer

## Installation

```bash
composer require forge/archive
```

## Formats

| Format | Extension | Extract | Create |
| --- | --- | --- | --- |
| `Format::Zip` | `.zip` | `tar` | `ditto` |
| `Format::Tar` | `.tar` | `tar` | `tar` |
| `Format::TarGz` | `.tar.gz` | `tar` | `tar` |
| `Format::TarXz` | `.tar.xz` | `tar` | `tar` |
| `Format::TarBz2` | `.tar.bz2` | `tar` | `tar` |

The format is detected from the file name. Pass it explicitly when the file has no extension:

```php
$archive->extract('/tmp/download', '/opt/tool', Format::TarXz);
```

## Usage

### Extract

```php
$archive->extract('release.tar.gz', 'build');
```

The archive is inspected first and extracted into a temporary directory next to the destination. Only a fully successful extraction is moved into place, so a failure never leaves partial files behind. Existing destinations are merged.

### Create

```php
$archive->create('src', 'dist/src.tar.xz', Format::TarXz);
$archive->create('README.md', 'dist/readme.zip', Format::Zip);
```

Directories keep their name as the archive root. Archives are created without macOS metadata (extended attributes, resource forks, quarantine flags and `._` files), so they unpack cleanly on any system.

### Inspect

```php
$archive->entries('release.tar.gz');
$archive->nodes('release.tar.gz');
```

`entries()` lists the paths inside the archive. `nodes()` returns a `Node` for every entry with its path, type, link target and size.

## Security

Every archive is treated as untrusted input. Before anything is written to disk, extraction rejects:

| Threat | Protection |
| --- | --- |
| Path traversal (zip slip) such as `../evil` | Every entry must resolve inside the destination |
| Absolute paths such as `/etc/passwd` | Rejected |
| Symbolic links pointing outside the destination | Link targets are resolved and must stay inside |
| FIFOs, character and block devices, sockets | Only files, directories and symbolic links are allowed |
| Decompression bombs | The total uncompressed size is limited, 8 GiB by default |

During and after extraction:

- Partial extraction never reaches the destination, it happens in a staging directory first.
- Owner permissions are restored (`u+rwX`), so an archive cannot make its own files unreadable or undeletable.
- setuid and setgid bits are not preserved.
- Hard links outside the destination are refused by `tar`.

When creating archives, file names are passed after `--`, so a name such as `--use-compress-program=...` is never treated as an option.

All tools are executed without a shell and from their absolute system paths (`/usr/bin/tar`, `/usr/bin/ditto`, `/bin/chmod`). These locations are protected by System Integrity Protection, so a different `tar` earlier in `PATH` is never used.

The size limit is configurable:

```php
use Forge\Archive\Extractor;

$archive = new Archive(new Extractor(limit: 2 * 1024 ** 3));
```

### Trust model

The package protects the file system from the contents of an archive. It does not decide whether an archive is trustworthy: verify the source and checksum of every download before extracting it.

## Exceptions

Every exception extends `Forge\Archive\Exception\ArchiveException`.

| Exception | Thrown when |
| --- | --- |
| `ArchiveNotFoundException` | The archive does not exist |
| `SourceNotFoundException` | The source to compress does not exist |
| `UnsupportedFormatException` | The format cannot be detected |
| `UnsafeEntryException` | An entry, link or entry type is unsafe |
| `ArchiveTooLargeException` | The archive exceeds the size limit |
| `InspectionFailedException` | The archive cannot be read |
| `ExtractionFailedException` | Extraction failed |
| `CompressionFailedException` | Creation failed |

## License

MIT, see [LICENSE](LICENSE).

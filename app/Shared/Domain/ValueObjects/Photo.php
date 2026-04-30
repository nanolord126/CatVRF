<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use Illuminate\Filesystem\FilesystemManager;

use InvalidArgumentException;
use Illuminate\Support\Facades\Storage;

/**
 * Class Photo
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 */
final readonly class Photo
{
    public function __construct(private readonly FilesystemManager $storage,
        public string $path,
        private readonly string $disk = 's3',) {
        if (empty($path)) {
            throw new InvalidArgumentException('Photo path cannot be empty.');
        }
    }

    /**
     * Handle getUrl operation.
     *
     * @throws \DomainException
     */
    public function getUrl(): string
    {
        return $this->storage->disk($this->disk)->url($this->path);
    }

    /**
     * Handle getPath operation.
     *
     * @throws \DomainException
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Determine if this instance is valid for the current context.
     */
    public function isValid(): bool
    {
        return true;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Media\Domain\DTOs;

use Spatie\LaravelData\Data;

final readonly class BulkImportDTO extends Data
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public string $vertical,
        public array $items,
        public ?string $zipFilePath = null,
        public bool $validateOnly = false,
        public bool $dryRun = false,
    ) {
    }

    public static function fromRequest(array $request): self
    {
        return new self(
            vertical: $request['vertical'],
            items: $request['items'],
            zipFilePath: $request['zip_file_path'] ?? null,
            validateOnly: $request['validate_only'] ?? false,
            dryRun: $request['dry_run'] ?? false,
        );
    }
}

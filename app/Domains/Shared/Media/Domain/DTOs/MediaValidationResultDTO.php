<?php

declare(strict_types=1);

namespace Modules\Media\Domain\DTOs;

use Spatie\LaravelData\Data;

final readonly class MediaValidationResultDTO extends Data
{
    /**
     * @param array<int, string> $errors
     * @param array<int, string> $warnings
     */
    public function __construct(
        public bool $valid,
        public array $errors = [],
        public array $warnings = [],
        public ?string $correlationId = null,
    ) {
        $this->correlationId ??= (string) \Illuminate\Support\Str::uuid();
    }

    public static function valid(): self
    {
        return new self(valid: true);
    }

    public static function invalid(array $errors): self
    {
        return new self(valid: false, errors: $errors);
    }

    public static function withWarnings(array $warnings): self
    {
        return new self(valid: true, warnings: $warnings);
    }
}

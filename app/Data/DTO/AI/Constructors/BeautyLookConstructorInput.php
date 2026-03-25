<?php

declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

use Spatie\LaravelData\Data;
use Illuminate\Http\UploadedFile;

final class BeautyLookConstructorInput extends Data
{
    public function __construct(
        public readonly int $userId,
        public readonly UploadedFile $photo,
        public readonly string $occasion,
        public readonly ?string $desiredStyle,
        public readonly string $budgetLevel,
        public readonly string $correlationId,
    ) {
    }
}

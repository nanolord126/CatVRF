<?php declare(strict_types=1);

namespace App\Data\DTO\AI\Constructors;

use Illuminate\Http\UploadedFile;

final readonly class BeautyLookConstructorInput
{
    public function __construct(
        public int $userId,
        public UploadedFile $photo,
        public string $occasion,
        public ?string $desiredStyle,
        public string $budgetLevel,
        public string $correlationId,
    ) {
    }
}

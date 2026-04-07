<?php

declare(strict_types=1);

namespace Modules\Bonuses\Application\DTOs;

use Spatie\LaravelData\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class AwardBonusData extends Data
{
    public function __construct(
        public readonly int $userId,
        public readonly int $amount,
        public readonly string $type,
        public readonly ?string $reason,
        public readonly ?int $sourceId,
        public readonly ?string $sourceType,
        public readonly ?string $correlationId,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->input('user_id'),
            amount: (int) $request->input('amount'),
            type: $request->input('type'),
            reason: $request->input('reason'),
            sourceId: $request->input('source_id'),
            sourceType: $request->input('source_type'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) Str::uuid(),
        );
    }
}

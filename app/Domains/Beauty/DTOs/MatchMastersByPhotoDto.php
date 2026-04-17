<?php declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

final readonly class MatchMastersByPhotoDto
{
    public function __construct(
        public int $tenantId,
        public int $userId,
        public UploadedFile $photo,
        public string $correlationId,
        public ?int $salonId = null,
        public ?string $preferredStyle = null,
        public ?float $maxDistance = null,
        public ?int $limit = null,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request, int $userId, int $tenantId): self
    {
        return new self(
            tenantId: $tenantId,
            userId: $userId,
            photo: $request->file('photo'),
            correlationId: $request->header('X-Correlation-ID') ?? Str::uuid()->toString(),
            salonId: $request->input('salon_id'),
            preferredStyle: $request->input('preferred_style'),
            maxDistance: $request->input('max_distance') ? (float) $request->input('max_distance') : null,
            limit: $request->input('limit') ? (int) $request->input('limit') : 10,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'photo_path' => $this->photo->getRealPath(),
            'photo_name' => $this->photo->getClientOriginalName(),
            'photo_size' => $this->photo->getSize(),
            'photo_mime' => $this->photo->getMimeType(),
            'correlation_id' => $this->correlationId,
            'salon_id' => $this->salonId,
            'preferred_style' => $this->preferredStyle,
            'max_distance' => $this->maxDistance,
            'limit' => $this->limit ?? 10,
        ];
    }
}

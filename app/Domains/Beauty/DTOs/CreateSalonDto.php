<?php
declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

final readonly class CreateSalonDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $name,
        public string $address,
        public float $lat,
        public float $lon,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public array $tags = []
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request, string $correlationId): self
    {
        return new self(
            (int) ($request->user()->tenant_id ?? 1),
            $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            $request->input('name', ''),
            $request->input('address', ''),
            (float) $request->input('lat', 0.0),
            (float) $request->input('lon', 0.0),
            $correlationId,
            $request->header('X-Idempotency-Key'),
            (array) $request->input('tags', [])
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'name' => $this->name,
            'address' => $this->address,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'correlation_id' => $this->correlationId,
            'tags' => json_encode($this->tags, JSON_THROW_ON_ERROR),
            'status' => 'active',
            'is_active' => true,
        ];
    }
}

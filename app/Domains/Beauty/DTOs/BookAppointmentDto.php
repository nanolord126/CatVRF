<?php
declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

final readonly class BookAppointmentDto
{
    public function __construct(
        public int $tenantId,
        public int $salonId,
        public int $masterId,
        public int $serviceId,
        public int $userId,
        public string $correlationId,
        public string $startsAt,
        public bool $isB2b = false
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request, string $correlationId): self
    {
        return new self(
            (int) ($request->user()->tenant_id ?? 1),
            (int) $request->input('salon_id'),
            (int) $request->input('master_id'),
            (int) $request->input('service_id'),
            (int) $request->user()->id,
            $correlationId,
            $request->input('starts_at'),
            $request->input('is_b2b', false)
        );
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class CalculatePriceDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public int $courseId,
        public string $correlationId,
        public ?string $idempotencyKey = null,
        public ?bool $isCorporate = null,
        public ?int $userId = null,
        public ?string $userSegment = null,
        public ?int $enrollmentCount = null,
        public ?string $timeSlot = null,
    ) {}

    public static function from(\Illuminate\Http\Request $request): self
    {
        $isB2B = $request->has('inn') && $request->has('business_card_id');

        return new self(
            tenantId: (int) tenant()->id,
            businessGroupId: $isB2B ? (int) $request->input('business_card_id') : null,
            courseId: (int) $request->input('course_id'),
            correlationId: $request->header('X-Correlation-ID') ?? (string) \Illuminate\Support\Str::uuid(),
            idempotencyKey: $request->header('X-Idempotency-Key'),
            isCorporate: $isB2B,
            userId: $request->input('user_id') ? (int) $request->input('user_id') : null,
            userSegment: $request->input('user_segment'),
            enrollmentCount: $request->input('enrollment_count') ? (int) $request->input('enrollment_count') : null,
            timeSlot: $request->input('time_slot'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'course_id' => $this->courseId,
            'correlation_id' => $this->correlationId,
            'is_corporate' => $this->isCorporate,
            'user_id' => $this->userId,
            'user_segment' => $this->userSegment,
            'enrollment_count' => $this->enrollmentCount,
            'time_slot' => $this->timeSlot,
        ];
    }
}

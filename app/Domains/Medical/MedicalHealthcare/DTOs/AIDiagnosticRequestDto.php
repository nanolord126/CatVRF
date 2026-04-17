<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\DTOs;

use Illuminate\Http\Request;

final readonly class AIDiagnosticRequestDto
{
    public function __construct(
        public int $userId,
        public array $symptoms,
        public string $additionalContext,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(Request $request): self
    {
        $userId = $request->has('user_id') ? intval($request->input('user_id')) : 0;
        
        return new self(
            userId: $userId,
            symptoms: array_map('strval', $request->input('symptoms', [])),
            additionalContext: strval($request->input('additional_context', '')),
            correlationId: strval($request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString())),
            idempotencyKey: $request->input('idempotency_key') ? strval($request->input('idempotency_key')) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'symptoms' => $this->symptoms,
            'additional_context' => $this->additionalContext,
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}

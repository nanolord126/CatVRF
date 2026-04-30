<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\MedicalHealthcare\Events;

use App\Domains\Shared\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final readonly class EmergencyDetectedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $userId,
        public AIDiagnosticResultDto $diagnosticResult,
        public string $correlationId,
    ) {}
}

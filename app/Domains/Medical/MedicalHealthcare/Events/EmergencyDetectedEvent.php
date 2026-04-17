<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Events;

use App\Domains\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
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

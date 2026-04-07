<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\MedicalSupplies\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class PrescriptionService
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

        public function validatePrescription(string $prescriptionCode, int $medicineId, string $correlationId): bool
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    $this->db->transaction(function () use ($prescriptionCode, $medicineId, $correlationId) {
                    $prescription = $this->db->table('prescriptions')
                        ->where('code', $prescriptionCode)
                        ->where('medicine_id', $medicineId)
                        ->where('is_used', false)
                        ->lockForUpdate()
                        ->first();

                    if (!$prescription) {
                        throw new \RuntimeException('Prescription not found or already used');
                    }

                    // Марк как использованный
                    $this->db->table('prescriptions')
                        ->where('id', $prescription->id)
                        ->update(['is_used' => true, 'used_at' => now()]);

                    $this->logger->info('Prescription validated', [
                        'prescription_id' => $prescription->id,
                        'medicine_id' => $medicineId,
                        'correlation_id' => $correlationId,
                    ]);

                    return true;
                });
            } catch (\Throwable $e) {
                $this->logger->error('Prescription validation failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}

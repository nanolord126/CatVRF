<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\DTOs\CarImportDto;
use App\Domains\Auto\Models\AutoVehicle;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\FraudMLService;
use App\Services\WalletService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class CarImportService
{
    private const CUSTOMS_RATES = [
        'euro' => 0.48,
        'usd' => 0.54,
        'jpy' => 0.08,
        'cny' => 0.08,
        'krw' => 0.06,
    ];

    private const ENGINE_RATES = [
        'electric' => 0.0,
        'hybrid' => 0.15,
        'petrol_under_3l' => 0.48,
        'petrol_over_3l' => 0.85,
        'diesel_under_3l' => 0.54,
        'diesel_over_3l' => 0.90,
    ];

    private const AGE_MULTIPLIERS = [
        '0-3' => 1.0,
        '3-5' => 1.2,
        '5-10' => 1.5,
        '10-15' => 2.0,
        '15+' => 2.5,
    ];

    public function __construct(
        private FraudControlService $fraudControl,
        private FraudMLService $fraudML,
        private AuditService $auditService,
        private WalletService $walletService,
        private ConnectionInterface $db,
        private Logger $logger,
    ) {}

    public function calculateCustomsDuties(CarImportDto $dto): array
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $dto->userId,
            operationType: 'car_import_calculation',
            amount: 0,
            ipAddress: $dto->ipAddress,
            deviceFingerprint: $dto->deviceFingerprint,
            correlationId: $correlationId,
        );

        $this->fraudML->checkVinFraud($dto->vin, $dto->userId, $correlationId);

        $cacheKey = "car_import:calc:$dto->tenantId:$dto->userId:" . md5($dto->vin . $dto->declaredValue . $dto->currency);
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $result = $this->db->transaction(function () use ($dto, $correlationId, $cacheKey) {
            $baseDutyRate = self::CUSTOMS_RATES[$dto->currency] ?? 0.48;
            $engineRate = $this->getEngineRate($dto->engineType, $dto->engineVolume);
            $ageMultiplier = $this->getAgeMultiplier($dto->manufactureYear);

            $customsDuty = $dto->declaredValue * $baseDutyRate * $ageMultiplier;
            $exciseTax = $dto->declaredValue * $engineRate * $ageMultiplier;
            $vat = ($dto->declaredValue + $customsDuty + $exciseTax) * 0.20;
            $recyclingFee = $this->calculateRecyclingFee($dto->engineType, $dto->engineVolume);
            $totalDuties = $customsDuty + $exciseTax + $vat + $recyclingFee;

            $exchangeRate = $this->getExchangeRate($dto->currency);
            $totalDutiesRUB = $totalDuties * $exchangeRate;

            $restrictions = $this->checkImportRestrictions($dto->vin, $dto->country, $dto->manufactureYear);

            $calculationResult = [
                'success' => true,
                'vin' => $dto->vin,
                'declared_value' => $dto->declaredValue,
                'currency' => $dto->currency,
                'exchange_rate' => $exchangeRate,
                'customs_duty' => [
                    'base_rate' => $baseDutyRate,
                    'amount' => $customsDuty,
                    'amount_rub' => $customsDuty * $exchangeRate,
                ],
                'excise_tax' => [
                    'engine_rate' => $engineRate,
                    'amount' => $exciseTax,
                    'amount_rub' => $exciseTax * $exchangeRate,
                ],
                'vat' => [
                    'rate' => 0.20,
                    'amount' => $vat,
                    'amount_rub' => $vat * $exchangeRate,
                ],
                'recycling_fee' => [
                    'amount' => $recyclingFee,
                    'amount_rub' => $recyclingFee * $exchangeRate,
                ],
                'total_duties' => [
                    'amount' => $totalDuties,
                    'amount_rub' => $totalDutiesRUB,
                ],
                'restrictions' => $restrictions,
                'estimated_import_cost' => [
                    'amount' => $dto->declaredValue + $totalDuties,
                    'amount_rub' => ($dto->declaredValue + $totalDuties) * $exchangeRate,
                ],
                'correlation_id' => $correlationId,
            ];

            $this->saveImportCalculation($dto, $calculationResult, $correlationId);

            Cache::put($cacheKey, $calculationResult, 3600);

            $this->auditService->record(
                action: 'car_import_calculated',
                subjectType: AutoVehicle::class,
                subjectId: 0,
                oldValues: [],
                newValues: [
                    'vin' => $dto->vin,
                    'total_duties_rub' => $totalDutiesRUB,
                    'has_restrictions' => count($restrictions) > 0,
                ],
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('car.import.calculation.completed', [
                'correlation_id' => $correlationId,
                'user_id' => $dto->userId,
                'vin' => $dto->vin,
                'total_duties_rub' => $totalDutiesRUB,
            ]);

            return $calculationResult;
        });

        return $result;
    }

    public function initiateImportProcess(CarImportDto $dto, array $documents): array
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $dto->userId,
            operationType: 'car_import_initiate',
            amount: $dto->declaredValue,
            ipAddress: $dto->ipAddress,
            deviceFingerprint: $dto->deviceFingerprint,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($dto, $documents, $correlationId) {
            $vehicle = $this->getOrCreateVehicle($dto, $correlationId);

            $importRecord = DB::table('car_imports')->insertGetId([
                'tenant_id' => $dto->tenantId,
                'business_group_id' => $dto->businessGroupId,
                'user_id' => $dto->userId,
                'vehicle_id' => $vehicle->id,
                'vin' => $dto->vin,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'country_origin' => $dto->country,
                'declared_value' => $dto->declaredValue,
                'currency' => $dto->currency,
                'engine_type' => $dto->engineType,
                'engine_volume' => $dto->engineVolume,
                'manufacture_year' => $dto->manufactureYear,
                'documents' => json_encode($documents),
                'status' => 'pending_payment',
                'is_b2b' => $dto->isB2b,
                'metadata' => [
                    'created_via_import_service' => true,
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $calculation = $this->calculateCustomsDuties($dto);
            $totalDuties = $calculation['total_duties']['amount_rub'];

            $this->auditService->record(
                action: 'car_import_initiated',
                subjectType: 'car_import',
                subjectId: $importRecord,
                oldValues: [],
                newValues: [
                    'vin' => $dto->vin,
                    'total_duties_rub' => $totalDuties,
                    'is_b2b' => $dto->isB2b,
                ],
                correlationId: $correlationId,
            );

            return [
                'success' => true,
                'import_id' => $importRecord,
                'vehicle_id' => $vehicle->id,
                'total_duties_rub' => $totalDuties,
                'status' => 'pending_payment',
                'correlation_id' => $correlationId,
            ];
        });
    }

    public function payCustomsDuties(int $importId, int $userId, int $tenantId, string $correlationId): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'car_import_payment',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($importId, $userId, $tenantId, $correlationId) {
            $import = DB::table('car_imports')
                ->where('id', $importId)
                ->where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($import === null) {
                throw new RuntimeException('Import record not found');
            }

            if ($import->status !== 'pending_payment') {
                throw new RuntimeException('Import is not in pending payment status');
            }

            $walletId = $this->getUserWalletId($userId, $tenantId);
            $calculation = $this->calculateCustomsDuties($this->createDtoFromImport($import));
            $totalDuties = $calculation['total_duties']['amount_rub'];

            $this->walletService->debit(
                walletId: $walletId,
                amount: (int) ($totalDuties * 100),
                reason: 'car_import_customs_duties',
                correlationId: $correlationId,
            );

            DB::table('car_imports')
                ->where('id', $importId)
                ->update([
                    'status' => 'customs_processing',
                    'paid_at' => now(),
                    'metadata' => array_merge(json_decode($import->metadata ?? '{}', true), [
                        'payment_completed_at' => now()->toIso8601String(),
                        'paid_amount' => $totalDuties,
                    ]),
                    'updated_at' => now(),
                ]);

            $this->auditService->record(
                action: 'car_import_duties_paid',
                subjectType: 'car_import',
                subjectId: $importId,
                oldValues: ['status' => 'pending_payment'],
                newValues: ['status' => 'customs_processing', 'paid_amount' => $totalDuties],
                correlationId: $correlationId,
            );

            return [
                'success' => true,
                'import_id' => $importId,
                'status' => 'customs_processing',
                'paid_amount' => $totalDuties,
                'correlation_id' => $correlationId,
            ];
        });
    }

    private function getEngineRate(string $engineType, ?float $engineVolume): float
    {
        if ($engineType === 'electric') {
            return self::ENGINE_RATES['electric'];
        }

        if ($engineType === 'hybrid') {
            return self::ENGINE_RATES['hybrid'];
        }

        if ($engineVolume === null) {
            return self::ENGINE_RATES['petrol_under_3l'];
        }

        $isOver3L = $engineVolume > 3.0;

        if ($engineType === 'diesel') {
            return $isOver3L ? self::ENGINE_RATES['diesel_over_3l'] : self::ENGINE_RATES['diesel_under_3l'];
        }

        return $isOver3L ? self::ENGINE_RATES['petrol_over_3l'] : self::ENGINE_RATES['petrol_under_3l'];
    }

    private function getAgeMultiplier(int $manufactureYear): float
    {
        $age = date('Y') - $manufactureYear;

        return match (true) {
            $age <= 3 => self::AGE_MULTIPLIERS['0-3'],
            $age <= 5 => self::AGE_MULTIPLIERS['3-5'],
            $age <= 10 => self::AGE_MULTIPLIERS['5-10'],
            $age <= 15 => self::AGE_MULTIPLIERS['10-15'],
            default => self::AGE_MULTIPLIERS['15+'],
        };
    }

    private function calculateRecyclingFee(string $engineType, ?float $engineVolume): float
    {
        if ($engineType === 'electric') {
            return 3000.0;
        }

        if ($engineVolume === null) {
            return 20000.0;
        }

        return $engineVolume > 3.0 ? 52000.0 : 20000.0;
    }

    private function getExchangeRate(string $currency): float
    {
        $rates = [
            'euro' => 98.5,
            'usd' => 92.3,
            'jpy' => 0.62,
            'cny' => 12.8,
            'krw' => 0.069,
        ];

        return $rates[$currency] ?? 98.5;
    }

    private function checkImportRestrictions(string $vin, string $country, int $manufactureYear): array
    {
        $restrictions = [];

        $sanctionedCountries = ['US', 'UK', 'EU', 'JP'];
        if (in_array($country, $sanctionedCountries, true)) {
            $restrictions[] = [
                'type' => 'sanctions',
                'severity' => 'high',
                'message' => 'Import from sanctioned countries restricted',
            ];
        }

        if ($manufactureYear < 2010) {
            $restrictions[] = [
                'type' => 'age_restriction',
                'severity' => 'medium',
                'message' => 'Vehicles older than 2010 may have additional requirements',
            ];
        }

        $restrictedModels = $this->getRestrictedModels();
        $vinPrefix = substr($vin, 0, 3);
        if (in_array($vinPrefix, $restrictedModels, true)) {
            $restrictions[] = [
                'type' => 'model_restriction',
                'severity' => 'high',
                'message' => 'This vehicle model is restricted for import',
            ];
        }

        return $restrictions;
    }

    private function getRestrictedModels(): array
    {
        return ['JTD', '1F1', '1G1'];
    }

    private function saveImportCalculation(CarImportDto $dto, array $result, string $correlationId): void
    {
        DB::table('car_import_calculations')->insert([
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'vin' => $dto->vin,
            'calculation_data' => json_encode($result),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getOrCreateVehicle(CarImportDto $dto, string $correlationId): AutoVehicle
    {
        $vehicle = AutoVehicle::where('vin', $dto->vin)
            ->where('tenant_id', $dto->tenantId)
            ->first();

        if ($vehicle !== null) {
            return $vehicle;
        }

        return AutoVehicle::create([
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'uuid' => Str::uuid()->toString(),
            'vin' => $dto->vin,
            'correlation_id' => $correlationId,
            'metadata' => [
                'created_via_import_service' => true,
                'country_origin' => $dto->country,
            ],
        ]);
    }

    private function getUserWalletId(int $userId, int $tenantId): int
    {
        $wallet = DB::table('wallets')
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($wallet !== null) {
            return $wallet->id;
        }

        return DB::table('wallets')->insertGetId([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'current_balance' => 0,
            'hold_amount' => 0,
            'correlation_id' => Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDtoFromImport(object $import): CarImportDto
    {
        return new CarImportDto(
            tenantId: $import->tenant_id,
            businessGroupId: $import->business_group_id,
            userId: $import->user_id,
            vin: $import->vin,
            country: $import->country_origin,
            declaredValue: $import->declared_value,
            currency: $import->currency,
            engineType: $import->engine_type,
            engineVolume: $import->engine_volume,
            manufactureYear: $import->manufacture_year,
            correlationId: $import->correlation_id,
            ipAddress: null,
            deviceFingerprint: null,
            isB2b: $import->is_b2b ?? false,
        );
    }
}

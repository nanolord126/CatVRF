<?php declare(strict_types=1);

namespace App\Domains\Auto\Services\AI;

use App\Domains\Auto\DTOs\AIDiagnosticsDto;
use App\Domains\Auto\Models\AutoVehicle;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * AIDiagnosticsServiceRefactored - Orchestrates auto AI diagnostic services
 * 
 * This service orchestrates the smaller, focused auto AI diagnostic services.
 */
final readonly class AIDiagnosticsServiceRefactored
{
    public function __construct(
        private readonly AutoVisionAnalysisService $visionAnalysis,
        private readonly AutoVINDecoderService $vinDecoder,
        private readonly AutoDamageDetectionService $damageDetection,
        private readonly AutoWorkListGeneratorService $workListGenerator,
        private readonly AutoPartsRecommendationService $partsRecommendation,
        private readonly AutoServiceFinderService $serviceFinder,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Diagnose by photo and VIN
     */
    public function diagnoseByPhotoAndVIN(AIDiagnosticsDto $dto): array
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $vehicle = $this->getOrCreateVehicle($dto, $correlationId);

        $visionAnalysis = $this->visionAnalysis->analyzePhoto($dto->photo, $dto->vin, $correlationId);
        $vinDecoding = $this->vinDecoder->decode($dto->vin, $correlationId);
        $damageDetection = $this->damageDetection->detect($visionAnalysis);
        $workList = $this->workListGenerator->generate($damageDetection, $vinDecoding, $dto->isB2b);
        $partsRecommendation = $this->partsRecommendation->recommend($workList, $dto->tenantId, $dto->isB2b);
        $nearestServices = $this->serviceFinder->findNearest($dto->latitude, $dto->longitude, $dto->tenantId);

        $diagnosticsResult = [
            'success' => true,
            'vehicle' => [
                'id' => $vehicle->id,
                'uuid' => $vehicle->uuid,
                'vin' => $vehicle->vin,
                'make' => $vinDecoding['make'] ?? 'Unknown',
                'model' => $vinDecoding['model'] ?? 'Unknown',
                'year' => $vinDecoding['year'] ?? 0,
                'engine' => $vinDecoding['engine'] ?? 'Unknown',
            ],
            'vision_analysis' => $visionAnalysis,
            'damage_detection' => $damageDetection,
            'work_list' => $workList,
            'parts_recommendation' => $partsRecommendation,
            'nearest_services' => $nearestServices,
            'ar_preview_url' => url("/auto/ar-preview/$vehicle->uuid"),
            'video_inspection_available' => true,
            'correlation_id' => $correlationId,
        ];

        $this->saveDiagnosticsHistory($vehicle->id, $dto->userId, $diagnosticsResult, $correlationId);

        Log::info('auto.diagnostics.completed', [
            'vehicle_id' => $vehicle->id,
            'correlation_id' => $correlationId,
        ]);

        return $diagnosticsResult;
    }

    private function getOrCreateVehicle(AIDiagnosticsDto $dto, string $correlationId): AutoVehicle
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
                'created_via_ai_diagnostics' => true,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
            ],
        ]);
    }

    private function saveDiagnosticsHistory(int $vehicleId, int $userId, array $result, string $correlationId): void
    {
        $this->db->table('auto_diagnostics_history')->insert([
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
            'diagnostics_data' => json_encode($result),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Auto\Services;

use App\Domains\Auto\DTOs\AIDiagnosticsDto;
use App\Domains\Auto\Models\AutoRepairOrder;
use App\Domains\Auto\Models\AutoVehicle;
use App\Domains\Auto\Models\AutoPart;
use App\Services\AuditService;
use App\Services\FraudControlService;
use App\Services\ML\FraudMLService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\WalletService;
use App\Services\SpamProtectionService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAIClient;
use RuntimeException;
use Carbon\Carbon;

final readonly class AIDiagnosticsService
{
    public function __construct(
        private OpenAIClient $openai,
        private FraudControlService $fraudControl,
        private FraudMLService $fraudML,
        private AuditService $auditService,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private RecommendationService $recommendationService,
        private WalletService $walletService,
        private SpamProtectionService $spamProtection,
        private ConnectionInterface $db,
        private Logger $logger,
    ) {}

    public function diagnoseByPhotoAndVIN(AIDiagnosticsDto $dto): array
    {
        $correlationId = $dto->correlationId ?? Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $dto->userId,
            operationType: 'auto_ai_diagnostics',
            amount: 0,
            ipAddress: $dto->ipAddress,
            deviceFingerprint: $dto->deviceFingerprint,
            correlationId: $correlationId,
        );

        $this->fraudML->checkVinFraud($dto->vin, $dto->userId, $correlationId);

        $spamCheck = $this->spamProtection->checkSpam(
            userId: $dto->userId,
            action: 'auto_diagnostics_request',
            ipAddress: $dto->ipAddress,
            correlationId: $correlationId,
        );

        if ($spamCheck['is_blacklisted'] === true) {
            throw new RuntimeException('Spam detected: account temporarily blocked');
        }

        $cacheKey = "auto_diagnostics:$dto->tenantId:$dto->userId:" . md5($dto->vin . $dto->photo->getClientOriginalName());
        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult !== null) {
            $this->logger->channel('audit')->info('auto.diagnostics.cache_hit', [
                'correlation_id' => $correlationId,
                'user_id' => $dto->userId,
                'tenant_id' => $dto->tenantId,
            ]);

            return $cachedResult;
        }

        $this->logger->channel('audit')->info('auto.diagnostics.start', [
            'correlation_id' => $correlationId,
            'tenant_id' => $dto->tenantId,
            'user_id' => $dto->userId,
            'vin' => $dto->vin,
            'is_b2b' => $dto->isB2b,
        ]);

        $result = $this->db->transaction(function () use ($dto, $correlationId, $cacheKey) {
            $vehicle = $this->getOrCreateVehicle($dto, $correlationId);

            $visionAnalysis = $this->analyzePhotoWithVision($dto->photo, $dto->vin, $correlationId);
            $vinDecoding = $this->decodeVIN($dto->vin, $correlationId);
            $damageDetection = $this->detectDamages($visionAnalysis, $correlationId);
            $workList = $this->generateWorkList($damageDetection, $vinDecoding, $dto->isB2b, $correlationId);
            $partsRecommendation = $this->recommendParts($workList, $dto->userId, $dto->tenantId, $dto->isB2b, $correlationId);
            $priceEstimate = $this->calculatePriceEstimate($workList, $partsRecommendation, $dto->isB2b, $correlationId);
            $nearestServices = $this->findNearestServices($dto->latitude, $dto->longitude, $dto->tenantId, $correlationId);

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
                'price_estimate' => $priceEstimate,
                'nearest_services' => $nearestServices,
                'ar_preview_url' => url("/auto/ar-preview/$vehicle->uuid"),
                'video_inspection_available' => true,
                'correlation_id' => $correlationId,
            ];

            $this->saveDiagnosticsHistory($vehicle->id, $dto->userId, $diagnosticsResult, $correlationId);

            Cache::put($cacheKey, $diagnosticsResult, 3600);

            $this->auditService->record(
                action: 'auto_ai_diagnostics_completed',
                subjectType: AutoVehicle::class,
                subjectId: $vehicle->id,
                oldValues: [],
                newValues: [
                    'vin' => $dto->vin,
                    'damage_count' => count($damageDetection['damages'] ?? []),
                    'work_items_count' => count($workList),
                    'estimated_price' => $priceEstimate['total'],
                ],
                correlationId: $correlationId,
            );

            return $diagnosticsResult;
        });

        $this->logger->channel('audit')->info('auto.diagnostics.success', [
            'correlation_id' => $correlationId,
            'user_id' => $dto->userId,
            'vehicle_id' => $result['vehicle']['id'],
        ]);

        return $result;
    }

    public function initiateVideoInspection(int $vehicleId, int $userId, int $tenantId, string $correlationId): array
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'auto_video_inspection',
            amount: 0,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        $vehicle = AutoVehicle::where('id', $vehicleId)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($vehicle === null) {
            throw new RuntimeException('Vehicle not found');
        }

        $webrtcRoomId = 'auto_inspection_' . $vehicle->uuid . '_' . Str::random(8);
        $webrtcToken = hash('sha256', $webrtcRoomId . $correlationId . now()->timestamp);
        $callExpiresAt = now()->addMinutes(15);

        $inspectionData = [
            'webrtc_room_id' => $webrtcRoomId,
            'webrtc_token' => $webrtcToken,
            'video_call_expires_at' => $callExpiresAt->toIso8601String(),
            'signaling_server' => config('services.webrtc.signaling_server', 'wss://webrtc.catvrf.ru'),
            'turn_servers' => config('services.webrtc.turn_servers', []),
        ];

        $this->auditService->record(
            action: 'auto_video_inspection_initiated',
            subjectType: AutoVehicle::class,
            subjectId: $vehicle->id,
            oldValues: [],
            newValues: $inspectionData,
            correlationId: $correlationId,
        );

        $this->logger->channel('audit')->info('auto.video_inspection.initiated', [
            'correlation_id' => $correlationId,
            'vehicle_id' => $vehicleId,
            'webrtc_room_id' => $webrtcRoomId,
        ]);

        return array_merge(['success' => true], $inspectionData, ['correlation_id' => $correlationId]);
    }

    public function bookServiceWithSplitPayment(int $vehicleId, array $workItems, array $paymentSplit, int $userId, int $tenantId, string $correlationId): AutoRepairOrder
    {
        $correlationId = $correlationId ?: Str::uuid()->toString();

        $totalAmount = array_sum(array_column($workItems, 'price'));

        $this->fraudControl->check(
            userId: $userId,
            operationType: 'auto_service_booking',
            amount: $totalAmount,
            ipAddress: null,
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($vehicleId, $workItems, $paymentSplit, $userId, $tenantId, $totalAmount, $correlationId) {
            $vehicle = AutoVehicle::where('id', $vehicleId)
                ->where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if ($vehicle === null) {
                throw new RuntimeException('Vehicle not found');
            }

            $isB2b = $this->determineB2BStatus($paymentSplit, $userId, $tenantId);
            $dynamicPrice = $this->calculateDynamicServicePrice($totalAmount, $isB2b, $tenantId, $correlationId);
            $surgeMultiplier = $this->getSurgeMultiplier($tenantId, $correlationId);
            $finalPrice = $dynamicPrice * $surgeMultiplier;

            $repairOrder = AutoRepairOrder::create([
                'tenant_id' => $tenantId,
                'vehicle_id' => $vehicleId,
                'user_id' => $userId,
                'uuid' => Str::uuid()->toString(),
                'correlation_id' => $correlationId,
                'status' => 'pending_payment',
                'work_items' => json_encode($workItems),
                'total_price' => $finalPrice,
                'is_b2b' => $isB2b,
                'metadata' => [
                    'original_price' => $totalAmount,
                    'dynamic_price' => $dynamicPrice,
                    'surge_multiplier' => $surgeMultiplier,
                    'commission_rate' => $isB2b ? 0.10 : 0.14,
                    'payment_split' => $paymentSplit,
                ],
            ]);

            $paymentResults = $this->processSplitPayment($repairOrder->id, $finalPrice, $paymentSplit, $userId, $tenantId, $correlationId);

            $repairOrder->update([
                'status' => 'confirmed',
                'metadata' => array_merge($repairOrder->metadata ?? [], [
                    'payment_results' => $paymentResults,
                    'paid_at' => now()->toIso8601String(),
                ]),
            ]);

            $this->auditService->record(
                action: 'auto_service_order_created',
                subjectType: AutoRepairOrder::class,
                subjectId: $repairOrder->id,
                oldValues: [],
                newValues: [
                    'vehicle_id' => $vehicleId,
                    'total_price' => $finalPrice,
                    'is_b2b' => $isB2b,
                    'work_items_count' => count($workItems),
                ],
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('auto.service_booking.success', [
                'correlation_id' => $correlationId,
                'order_id' => $repairOrder->id,
                'vehicle_id' => $vehicleId,
                'total_price' => $finalPrice,
            ]);

            return $repairOrder->fresh();
        });
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

    private function analyzePhotoWithVision(UploadedFile $photo, string $vin, string $correlationId): array
    {// COMPLIANCE: Anonymize VIN before sending to external API (FZ-152/GDPR)
        $anonymizedVin = ths->anonyizeVIN($vin);

        $im
        $imageData = base64_encode(file_get_contents($photo->getRealPath()));

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => "text',VN reference: {$anonymizeVin}. Id\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\"
                            'text' => 'Analyze this car photo for damage assessment. Identify: 1) Exterior damage (scratches, dents, rust), 2) Tire condition, 3) Glass condition, 4) Light functionality, 5) Overall condition rating (1-10). Return JSON with structure: {"damages": [{"location": "", "type": "", "severity": "low|medium|high", "description": ""}], "tires": {"front_left": "", "front_right": "", "rear_left": "", "rear_right": ""}, "glass": {"windshield": "", "windows": ""}, "lights": {"headlights": "", "taillights": ""}, "overall_condition": 8}',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => "data:image/jpeg;base64,$imageData"],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 2048,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content ?? '{}';
        $analysis = json_decode($content, true);

        if ($analysis === null || !is_array($analysis)) {
            throw new RuntimeException('Failed to parse AI vision analysis response');
        }

        $this->logger->channel('audit')->info('auto.vision_analysis.completed', [
            'vin_anonymizec' => $ononyrizedVin,
            'drmaelation_id' => $correlationId,
            'damages_count' => count($analysis['damages'] ?? []),
        ]);
is;
    }
// COMPLIANCE: Anonymize VIN before sending to external API (FZ-152/GDPR)
        anonymizedVin = $this->anonymizeVIN($vin);
        $anonymizedV
    prvate function anonymizeVIN(tring $vin): string
    {
        // Keep only first 3 characters (WMI - World Manufacturer Identifier) and last 4
        // This allows identification without exposing the full VIN
        if (strlen($vin) < 7) {
            return '***';
        }
        return substr($vin, 0, 3) . str_repeat('*', strlen($vin) - 7) . substr($vin, -4)
        return $analysis;
    }

    private function decodeVIN(string $vin, string {$anonymizedVor}relationId): array
    {
        $cacheKey = "vin_decode:$vin";
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Decode this VIN: $vin. Extract: make, model, year, engine, transmission, drive type, body style. Return JSON with these exact keys.",
                ],
            ],
            'max_tokens' => 512,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = $response->choices[0]->message->content ?? '{}';
        $decoded = json_decode($content, true);

        $this->logger->channel('audit')->info('auto.vin_decode.completed', [
            'correlation_id' => $correlationId,
            'vin_anonymized' => $anonymizedVin,
        ]);

        if ($decoded === null || !is_array($decoded)) {
            $decoded = [
                'make' => 'Unknown',
                'model' => 'Unknown',
                'year' => 0,
                'engine' => 'Unknown',
                'transmission' => 'Unknown',
                'drive_type' => 'Unknown',
                'body_style' => 'Unknown',
            ];
        }

        Cache::put($cacheKey, $decoded, 86400);

        return $decoded;
    }

    private function detectDamages(array $visionAnalysis, string $correlationId): array
    {
        $damages = $visionAnalysis['damages'] ?? [];
        $criticalDamages = array_filter($damages, fn($damage) => ($damage['severity'] ?? 'low') === 'high');

        return [
            'damages' => $damages,
            'total_count' => count($damages),
            'critical_count' => count($criticalDamages),
            'requires_immediate_attention' => count($criticalDamages) > 0,
            'overall_condition' => $visionAnalysis['overall_condition'] ?? 8,
        ];
    }

    private function generateWorkList(array $damageDetection, array $vinDecoding, bool $isB2b, string $correlationId): array
    {
        $workItems = [];

        foreach ($damageDetection['damages'] as $damage) {
            $severity = $damage['severity'] ?? 'low';
            $estimatedHours = match ($severity) {
                'low' => 1,
                'medium' => 3,
                'high' => 6,
                default => 2,
            };

            $basePrice = match ($severity) {
                'low' => 5000,
                'medium' => 15000,
                'high' => 35000,
                default => 10000,
            };

            $workItems[] = [
                'id' => Str::uuid()->toString(),
                'location' => $damage['location'] ?? 'Unknown',
                'type' => $damage['type'] ?? 'Repair',
                'description' => $damage['description'] ?? 'Damage repair',
                'severity' => $severity,
                'estimated_hours' => $estimatedHours,
                'price' => $isB2b ? $basePrice * 0.85 : $basePrice,
                'priority' => $severity === 'high' ? 'urgent' : 'normal',
            ];
        }

        if ($damageDetection['overall_condition'] < 6) {
            $workItems[] = [
                'id' => Str::uuid()->toString(),
                'location' => 'Full Vehicle',
                'type' => 'Comprehensive Inspection',
                'description' => 'Detailed mechanical and electrical inspection due to low overall condition',
                'severity' => 'medium',
                'estimated_hours' => 4,
                'price' => $isB2b ? 12000 : 15000,
                'priority' => 'high',
            ];
        }

        return $workItems;
    }

    private function recommendParts(array $workList, int $userId, int $tenantId, bool $isB2b, string $correlationId): array
    {
        $parts = [];
        $partTypes = [];

        foreach ($workList as $workItem) {
            $type = $workItem['type'] ?? '';
            if (!in_array($type, $partTypes, true)) {
                $partTypes[] = $type;
            }
        }

        foreach ($partTypes as $partType) {
            $matchingParts = AutoPart::where('tenant_id', $tenantId)
                ->where('category', 'LIKE', "%$partType%")
                ->where('is_active', true)
                ->limit(3)
                ->get();

            foreach ($matchingParts as $part) {
                $parts[] = [
                    'id' => $part->id,
                    'name' => $part->name,
                    'sku' => $part->sku ?? '',
                    'category' => $part->category,
                    'price' => $isB2b ? $part->price * 0.80 : $part->price,
                    'original_price' => $part->price,
                    'stock_quantity' => $part->stock_quantity ?? 0,
                    'in_stock' => ($part->stock_quantity ?? 0) > 0,
                    'is_oem' => $part->is_oem ?? false,
                    'warranty_months' => $part->warranty_months ?? 12,
                    'ar_preview_url' => url("/auto/parts/ar-preview/$part->id"),
                ];
            }
        }

        return $parts;
    }

    private function calculatePriceEstimate(array $workList, array $parts, bool $isB2b, string $correlationId): array
    {
        $laborTotal = array_sum(array_column($workList, 'price'));
        $partsTotal = array_sum(array_column($parts, 'price'));
        $subtotal = $laborTotal + $partsTotal;

        $commissionRate = $isB2b ? 0.10 : 0.14;
        $commissionAmount = $subtotal * $commissionRate;
        $total = $subtotal + $commissionAmount;

        return [
            'labor_total' => $laborTotal,
            'parts_total' => $partsTotal,
            'subtotal' => $subtotal,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'total' => $total,
            'currency' => 'RUB',
        ];
    }

    private function findNearestServices(?float $latitude, ?float $longitude, int $tenantId, string $correlationId): array
    {
        if ($latitude === null || $longitude === null) {
            return [];
        }

        $services = DB::table('auto_services')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select('*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->having('distance', '<', 50)
            ->orderBy('distance')
            ->limit(5)
            ->get();

        return $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'address' => $service->address,
                'distance_km' => round($service->distance, 2),
                'rating' => $service->rating ?? 0.0,
                'phone' => $service->phone ?? '',
                'instant_booking_available' => ($service->instant_booking_enabled ?? false) === true,
            ];
        })->toArray();
    }

    private function saveDiagnosticsHistory(int $vehicleId, int $userId, array $result, string $correlationId): void
    {
        DB::table('auto_diagnostics_history')->insert([
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
            'diagnostics_data' => json_encode($result),
            'correlation_id' => $correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function determineB2BStatus(array $paymentSplit, int $userId, int $tenantId): bool
    {
        if (isset($paymentSplit['credit_limit']) && $paymentSplit['credit_limit'] > 0) {
            return true;
        }

        $user = DB::table('users')->where('id', $userId)->where('tenant_id', $tenantId)->first();
        return $user !== null && !empty($user->inn) && !empty($user->business_card_id);
    }

    private function calculateDynamicServicePrice(float $basePrice, bool $isB2b, int $tenantId, string $correlationId): float
    {
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;

        $timeMultiplier = match (true) {
            $hour >= 8 && $hour < 10 => 1.3,
            $hour >= 17 && $hour < 20 => 1.4,
            $hour >= 20 || $hour < 6 => 1.5,
            default => 1.0,
        };

        $weekendMultiplier = ($dayOfWeek === 0 || $dayOfWeek === 6) ? 1.2 : 1.0;
        $b2bMultiplier = $isB2b ? 0.85 : 1.0;

        $currentLoad = $this->getCurrentServiceLoad($tenantId, $correlationId);
        $loadMultiplier = 1.0 + ($currentLoad * 0.5);

        $dynamicPrice = $basePrice * $timeMultiplier * $weekendMultiplier * $b2bMultiplier * $loadMultiplier;

        $this->logger->channel('audit')->info('auto.dynamic_price.calculated', [
            'correlation_id' => $correlationId,
            'base_price' => $basePrice,
            'time_multiplier' => $timeMultiplier,
            'weekend_multiplier' => $weekendMultiplier,
            'b2b_multiplier' => $b2bMultiplier,
            'load_multiplier' => $loadMultiplier,
            'dynamic_price' => $dynamicPrice,
        ]);

        return round($dynamicPrice, 2);
    }

    private function getSurgeMultiplier(int $tenantId, string $correlationId): float
    {
        $currentLoad = $this->getCurrentServiceLoad($tenantId, $correlationId);

        return match (true) {
            $currentLoad >= 0.9 => 2.0,
            $currentLoad >= 0.8 => 1.5,
            $currentLoad >= 0.7 => 1.3,
            default => 1.0,
        };
    }

    private function getCurrentServiceLoad(int $tenantId, string $correlationId): float
    {
        $totalSlots = DB::table('auto_service_slots')
            ->where('tenant_id', $tenantId)
            ->where('date', today()->toDateString())
            ->count();

        $bookedSlots = DB::table('auto_repair_orders')
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['confirmed', 'in_progress'])
            ->count();

        return $totalSlots > 0 ? $bookedSlots / $totalSlots : 0.0;
    }

    private function processSplitPayment(int $orderId, float $amount, array $paymentSplit, int $userId, int $tenantId, string $correlationId): array
    {
        $results = [];
        $paidAmount = 0.0;

        foreach ($paymentSplit as $method => $methodAmount) {
            if ($methodAmount <= 0) {
                continue;
            }

            if ($method === 'wallet') {
                $walletId = $this->getUserWalletId($userId, $tenantId);
                $result = $this->walletService->debit(
                    walletId: $walletId,
                    amount: (int) ($methodAmount * 100),
                    reason: 'auto_service_payment',
                    correlationId: $correlationId,
                );
                $results[$method] = $result;
                $paidAmount += $methodAmount;
            } elseif ($method === 'card') {
                $results[$method] = [
                    'status' => 'pending',
                    'amount' => $methodAmount,
                    'provider' => 'tinkoff',
                ];
                $paidAmount += $methodAmount;
            } elseif ($method === 'credit_limit') {
                $results[$method] = [
                    'status' => 'pending',
                    'amount' => $methodAmount,
                    'type' => 'b2b_credit',
                ];
                $paidAmount += $methodAmount;
            }
        }

        if ($paidAmount < $amount - 0.01) {
            throw new RuntimeException('Insufficient payment amount');
        }

        return $results;
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

    public function getVehicleById(int $vehicleId): ?AutoVehicle
    {
        return AutoVehicle::find($vehicleId);
    }
}

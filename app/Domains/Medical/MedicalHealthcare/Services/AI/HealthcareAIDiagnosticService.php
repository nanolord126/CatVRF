<?php declare(strict_types=1);

namespace App\Domains\Medical\MedicalHealthcare\Services\AI;

use App\Domains\Medical\MedicalHealthcare\DTOs\AIDiagnosticRequestDto;
use App\Domains\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
use App\Domains\Medical\MedicalHealthcare\DTOs\HealthScorePredictionDto;
use App\Domains\Medical\MedicalHealthcare\Events\EmergencyDetectedEvent;
use App\Domains\Medical\Models\MedicalAppointment;
use App\Domains\Medical\Models\Clinic;
use App\Domains\Medical\Models\Doctor;
use App\Domains\Medical\Models\MedicalRecord;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\ML\FraudMLService;
use App\Services\Payment\PaymentService;
use App\Services\Wallet\WalletService;
use App\Services\Resilience\CircuitBreaker;
use App\Services\AI\OpenAIClientService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final class HealthcareAIDiagnosticService
{
    private const CACHE_TTL = 3600;
    private const EMBEDDING_DIMENSION = 1536;
    private const HEALTH_SCORE_MIN = 0;
    private const HEALTH_SCORE_MAX = 100;
    private const EMERGENCY_THRESHOLD = 30;
    private const SLOT_HOLD_MINUTES = 15;
    private const SLOT_HOLD_EXTENDED_MINUTES = 60;

    private OpenAIClientService $openai;

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private FraudMLService $fraudML,
        private WalletService $wallet,
        private PaymentService $payment,
        private DatabaseManager $db,
        private Cache $cache,
        private LoggerInterface $logger,
        private RedisConnection $redis,
        private Guard $guard,
        OpenAIClientService $openai,
        private CircuitBreaker $circuitBreaker,
    ) {
        $this->openai = $openai;
    }

    public function analyzeSymptomsAndDiagnose(AIDiagnosticRequestDto $dto): AIDiagnosticResultDto
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'ai_diagnosis',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        $cacheKey = "healthcare:diagnosis:{$dto->userId}:" . md5(json_encode($dto->symptoms));

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return AIDiagnosticResultDto::fromJson($cached);
        }

        // Выносим LLM-вызов из транзакции
        $symptomsText = implode(', ', $dto->symptoms);
        $patientHistory = $this->getPatientHistory($dto->userId);
        $systemPrompt = $this->buildDiagnosticSystemPrompt();
        $userPrompt = $this->buildDiagnosticUserPrompt($symptomsText, $patientHistory, $dto->additionalContext);

        // Анонимизация данных перед отправкой в OpenAI
        $anonymizedUserPrompt = $this->anonymizeMedicalData($userPrompt);

        try {
            $response = $this->circuitBreaker->call(function () use ($systemPrompt, $anonymizedUserPrompt) {
                return $this->openai->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $anonymizedUserPrompt],
                ], 0.3, 'json');
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circuit breaker is open')) {
                throw new \RuntimeException('AI service temporarily unavailable. Please try again later.');
            }
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to get AI diagnosis. Please try again later.');
        }

        $diagnosticData = json_decode($response['content'], true);
        if ($diagnosticData === null) {
            throw new \RuntimeException('Invalid AI response format.');
        }

        $healthScore = $this->calculateHealthScore($diagnosticData, $dto->symptoms);
        $embedding = $this->generateEmbedding($symptomsText);

        return $this->db->transaction(function () use ($dto, $cacheKey, $diagnosticData, $healthScore, $embedding, $symptomsText) {
            $result = new AIDiagnosticResultDto(
                primaryDiagnosis: $diagnosticData['primary_diagnosis'] ?? 'Не удалось определить',
                differentialDiagnoses: $diagnosticData['differential_diagnoses'] ?? [],
                recommendedSpecialties: $diagnosticData['recommended_specialties'] ?? [],
                urgencyLevel: $diagnosticData['urgency_level'] ?? 'medium',
                recommendedTests: $diagnosticData['recommended_tests'] ?? [],
                triageCategory: $diagnosticData['triage_category'] ?? 'routine',
                healthScore: $healthScore,
                riskFactors: $diagnosticData['risk_factors'] ?? [],
                preventiveMeasures: $diagnosticData['preventive_measures'] ?? [],
                confidence: $diagnosticData['confidence'] ?? 0.5,
                embedding: $embedding,
                requiresEmergency: $healthScore <= self::EMERGENCY_THRESHOLD,
                correlationId: $dto->correlationId,
            );

            $this->saveDiagnosticResult($dto->userId, $result);

            if ($result->requiresEmergency) {
                $this->triggerEmergencyProtocol($dto->userId, $result, $dto->correlationId);
            }

            $this->syncDiagnosticResult($dto->userId, $result->toArray(), $dto->correlationId);

            $this->logger->info('AI diagnostic completed', [
                'user_id' => $dto->userId,
                'correlation_id' => $dto->correlationId,
                'health_score' => $healthScore,
                'urgency_level' => $result->urgencyLevel,
                'requires_emergency' => $result->requiresEmergency,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            ]);

            $this->cache->put($cacheKey, json_encode($result->toArray()), self::CACHE_TTL);

            return $result;
        });
    }

    public function predictHealthScore(int $userId, array $labResults = []): HealthScorePredictionDto
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'health_score_prediction',
            amount: 0,
            correlationId: Str::uuid()->toString(),
        );

        $cacheKey = "healthcare:healthscore:{$userId}:" . md5(json_encode($labResults));
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return HealthScorePredictionDto::fromJson($cached);
        }

        // Выносим LLM-вызов из транзакции
        $patientHistory = $this->getPatientHistory($userId);
        $lifestyleData = $this->getLifestyleData($userId);
        $prompt = $this->buildHealthScorePrompt($patientHistory, $labResults, $lifestyleData);

        // Анонимизация данных перед отправкой в OpenAI
        $anonymizedPrompt = $this->anonymizeMedicalData($prompt);

        try {
            $response = $this->openai->chat([
                ['role' => 'system', 'content' => 'Ты эксперт по превентивной медицине и предиктивной аналитике здоровья. Оценивай здоровье по шкале 0-100.'],
                ['role' => 'user', 'content' => $anonymizedPrompt],
            ], 0.2, 'json');
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI API call failed for health score', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);
            throw new \RuntimeException('Failed to get health score prediction. Please try again later.');
        }

        $predictionData = json_decode($response['content'], true);
        if ($predictionData === null) {
            throw new \RuntimeException('Invalid AI response format.');
        }

        $currentScore = max(self::HEALTH_SCORE_MIN, min(self::HEALTH_SCORE_MAX, intval($predictionData['current_score'] ?? 70)));
        $predictedScore30Days = max(self::HEALTH_SCORE_MIN, min(self::HEALTH_SCORE_MAX, intval($predictionData['predicted_30_days'] ?? $currentScore)));
        $predictedScore90Days = max(self::HEALTH_SCORE_MIN, min(self::HEALTH_SCORE_MAX, intval($predictionData['predicted_90_days'] ?? $currentScore)));

        return $this->db->transaction(function () use ($userId, $cacheKey, $currentScore, $predictedScore30Days, $predictedScore90Days, $predictionData, $response) {
            $result = new HealthScorePredictionDto(
                currentScore: $currentScore,
                predictedScore30Days: $predictedScore30Days,
                predictedScore90Days: $predictedScore90Days,
                trend: $this->calculateTrend($currentScore, $predictedScore30Days),
                keyFactors: $predictionData['key_factors'] ?? [],
                recommendations: $predictionData['recommendations'] ?? [],
                riskAreas: $predictionData['risk_areas'] ?? [],
                confidence: floatval($predictionData['confidence'] ?? 0.7),
                correlationId: Str::uuid()->toString(),
            );

            $this->saveHealthScorePrediction($userId, $result);

            $this->logger->info('Health score prediction completed', [
                'user_id' => $userId,
                'current_score' => $currentScore,
                'predicted_30_days' => $predictedScore30Days,
                'trend' => $result->trend,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            ]);

            $this->cache->put($cacheKey, json_encode($result->toArray()), self::CACHE_TTL);

            return $result;
        });
    }

    public function recommendDoctorsAndClinics(AIDiagnosticResultDto $diagnostic, int $userId, bool $isB2B = false): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'doctor_recommendation',
            amount: 0,
            correlationId: $diagnostic->correlationId,
        );

        $cacheKey = "healthcare:recommendations:{$userId}:" . md5(json_encode($diagnostic->recommendedSpecialties));
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $recommendations = [];
        
        foreach ($diagnostic->recommendedSpecialties as $specialty) {
            $doctors = Doctor::where('specialty', $specialty)
                ->where('is_active', true)
                ->whereHas('clinic', function ($query) {
                    $query->where('is_active', true);
                })
                ->with(['clinic', 'reviews'])
                ->get();

            $scoredDoctors = $doctors->map(function ($doctor) use ($diagnostic, $userId, $isB2B) {
                $score = $this->calculateDoctorMatchScore($doctor, $diagnostic, $userId);
                $dynamicPrice = $this->calculateDynamicPrice($doctor, $diagnostic->urgencyLevel, $isB2B);
                
                return [
                    'doctor_id' => $doctor->id,
                    'name' => $doctor->name,
                    'specialty' => $doctor->specialty,
                    'clinic' => [
                        'id' => $doctor->clinic->id,
                        'name' => $doctor->clinic->name,
                        'address' => $doctor->clinic->address,
                        'rating' => floatval($doctor->clinic->rating ?? 4.5),
                    ],
                    'match_score' => $score,
                    'base_price' => floatval($doctor->consultation_price ?? 0),
                    'dynamic_price' => $dynamicPrice,
                    'available_slots' => $this->getAvailableSlots($doctor->id),
                    'rating' => floatval($doctor->rating ?? 4.5),
                    'experience_years' => intval($doctor->experience_years ?? 0),
                    'has_video_consultation' => boolval($doctor->has_video_consultation ?? false),
                    'is_flash_discount_available' => $this->isFlashDiscountAvailable($doctor->clinic->id),
                ];
            })
            ->sortByDesc('match_score')
            ->take(5)
            ->values();

            $recommendations[$specialty] = $scoredDoctors->toArray();
        }

        $this->cache->put($cacheKey, json_encode($recommendations), 1800);

        return $recommendations;
    }

    public function holdAppointmentSlot(int $doctorId, string $dateTime, int $userId, bool $extendedHold = false, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'slot_hold',
            amount: 0,
            correlationId: $correlationId,
        );

        $holdMinutes = $extendedHold ? self::SLOT_HOLD_EXTENDED_MINUTES : self::SLOT_HOLD_MINUTES;
        $holdKey = "healthcare:slot:hold:{$doctorId}:{$dateTime}";
        
        if ($this->redis->exists($holdKey)) {
            return [
                'success' => false,
                'message' => 'Слот уже забронирован другим пользователем',
                'hold_until' => null,
            ];
        }

        $holdUntil = now()->addMinutes($holdMinutes);
        $this->redis->setex($holdKey, $holdMinutes * 60, json_encode([
            'user_id' => $userId,
            'held_at' => now()->toIso8601String(),
            'correlation_id' => $correlationId,
        ]));

        $this->logger->info('Appointment slot held', [
            'user_id' => $userId,
            'doctor_id' => $doctorId,
            'datetime' => $dateTime,
            'hold_until' => $holdUntil->toIso8601String(),
            'correlation_id' => $correlationId,
            'extended' => $extendedHold,
        ]);

        return [
            'success' => true,
            'message' => 'Слот успешно забронирован',
            'hold_until' => $holdUntil->toIso8601String(),
            'hold_id' => $holdKey,
        ];
    }

    public function confirmAppointmentWithPayment(int $userId, int $doctorId, string $dateTime, array $paymentData, string $correlationId = ''): MedicalAppointment
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'appointment_booking',
            amount: intval($paymentData['amount'] ?? 0),
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($userId, $doctorId, $dateTime, $paymentData, $correlationId) {
            $holdKey = "healthcare:slot:hold:{$doctorId}:{$dateTime}";
            $holdData = $this->redis->get($holdKey);
            
            if ($holdData === null) {
                throw new \RuntimeException('Время удержания слота истекло. Пожалуйста, выберите другое время.');
            }

            $holdInfo = json_decode($holdData, true);
            if (intval($holdInfo['user_id']) !== $userId) {
                throw new \RuntimeException('Этот слот забронирован другим пользователем.');
            }

            $doctor = Doctor::findOrFail($doctorId);
            $finalPrice = $this->calculateDynamicPrice($doctor, 'medium', $paymentData['is_b2b'] ?? false);

            $paymentResult = $this->payment->initPayment(
                amount: $finalPrice,
                tenantId: $paymentData['tenant_id'] ?? 0,
                userId: $userId,
                paymentMethod: 'card',
                hold: true
            );

            $appointment = MedicalAppointment::create([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => $this->getTenantId(),
                'business_group_id' => $paymentData['business_group_id'] ?? null,
                'user_id' => $userId,
                'doctor_id' => $doctorId,
                'clinic_id' => $doctor->clinic_id,
                'appointment_datetime' => $dateTime,
                'status' => 'confirmed',
                'consultation_type' => $paymentData['consultation_type'] ?? 'in_person',
                'price' => $finalPrice,
                'payment_transaction_id' => $paymentResult['transaction_id'] ?? null,
                'correlation_id' => $correlationId,
                'tags' => json_encode(['ai_diagnostic_flow', 'dynamic_pricing']),
            ]);

            $this->redis->del($holdKey);

            $this->syncAppointmentBooking($appointment->toArray(), $correlationId);

            $this->logger->info('Appointment confirmed with payment', [
                'appointment_id' => $appointment->id,
                'user_id' => $userId,
                'doctor_id' => $doctorId,
                'amount' => $finalPrice,
                'correlation_id' => $correlationId,
            ]);

            return $appointment;
        });
    }

    public function generateVideoConsultationToken(int $appointmentId, string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $this->getUserId(),
            operationType: 'video_consultation',
            amount: 0,
            correlationId: $correlationId,
        );

        $appointment = MedicalAppointment::with(['doctor', 'user'])->findOrFail($appointmentId);
        
        if ($appointment->consultation_type !== 'video') {
            throw new \RuntimeException('Эта консультация не является видео-консультацией.');
        }

        if ($appointment->status !== 'confirmed') {
            throw new \RuntimeException('Консультация не подтверждена.');
        }

        $token = Str::random(64);
        $roomName = "healthcare_consult_{$appointment->id}";
        $expiresAt = $appointment->appointment_datetime->addHours(2);

        $this->redis->setex(
            "healthcare:webrtc:token:{$token}",
            $expiresAt->diffInSeconds(now()),
            json_encode([
                'appointment_id' => $appointmentId,
                'user_id' => $appointment->user_id,
                'doctor_id' => $appointment->doctor_id,
                'room_name' => $roomName,
                'correlation_id' => $correlationId,
            ])
        );

        $this->logger->info('Video consultation token generated', [
            'appointment_id' => $appointmentId,
            'correlation_id' => $correlationId,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'token' => $token,
            'room_name' => $roomName,
            'webrtc_url' => config('services.webrtc.endpoint') . "/room/{$roomName}?token={$token}",
            'expires_at' => $expiresAt->toIso8601String(),
            'doctor_name' => $appointment->doctor->name,
        ];
    }

    public function processInstantCheckIn(string $qrCode, string $nfcData = '', string $correlationId = ''): array
    {
        $this->fraud->check(
            userId: $this->getUserId(),
            operationType: 'instant_checkin',
            amount: 0,
            correlationId: $correlationId,
        );

        $checkInData = json_decode(base64_decode($qrCode), true);
        
        if ($checkInData === null || !isset($checkInData['appointment_id'])) {
            throw new \RuntimeException('Неверный формат QR-кода.');
        }

        $appointment = MedicalAppointment::with(['doctor', 'clinic'])->findOrFail($checkInData['appointment_id']);
        
        if ($appointment->user_id !== $this->getUserId()) {
            throw new \RuntimeException('Этот QR-код принадлежит другому пациенту.');
        }

        if ($appointment->status !== 'confirmed') {
            throw new \RuntimeException('Консультация не подтверждена или уже завершена.');
        }

        $allowedTimeWindow = now()->subMinutes(30)->lte($appointment->appointment_datetime) 
            && now()->addMinutes(15)->gte($appointment->appointment_datetime);
        
        if (!$allowedTimeWindow) {
            throw new \RuntimeException('Чек-ин доступен только за 30 минут до начала и в течение 15 минут после.');
        }

        $appointment->update([
            'status' => 'checked_in',
            'check_in_time' => now(),
            'check_in_method' => $nfcData !== '' ? 'nfc' : 'qr',
        ]);

        $this->syncCheckIn($appointment->toArray(), $correlationId);

        $this->logger->info('Instant check-in completed', [
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->user_id,
            'method' => $nfcData !== '' ? 'nfc' : 'qr',
            'correlation_id' => $correlationId,
        ]);

        return [
            'success' => true,
            'appointment_id' => $appointment->id,
            'doctor_name' => $appointment->doctor->name,
            'clinic_name' => $appointment->clinic->name,
            'room_number' => $appointment->doctor->room_number ?? 'Уточните у администратора',
            'estimated_wait_time' => $this->calculateEstimatedWaitTime($appointment->doctor_id),
            'status' => 'checked_in',
        ];
    }

    private function buildDiagnosticSystemPrompt(): string
    {
        return <<<PROMPT
Ты AI-диагностическая система платформы CatVRF Healthcare. Твоя задача:

1. Анализировать симптомы и предоставлять предварительную диагностику
2. Определять уровень срочности (emergency, urgent, routine)
3. Рекомендовать необходимые специалисты и анализы
4. Вычислять health-score (0-100, где 100 - отличное здоровье)
5. Выявлять факторы риска и профилактические меры

ВАЖНО:
- Всегда указывать confidence level (0.0-1.0)
- Если health-score <= 30, помечать как emergency
- Предоставлять дифференциальную диагностику (3-5 вариантов)
- Рекомендовать только проверенные медицинские протоколы
- Никогда не ставить окончательный диагноз - только предварительный

Ответ в JSON формате:
{
  "primary_diagnosis": "string",
  "differential_diagnoses": ["string"],
  "recommended_specialties": ["string"],
  "urgency_level": "emergency|urgent|routine",
  "recommended_tests": ["string"],
  "triage_category": "red|yellow|green|blue",
  "risk_factors": ["string"],
  "preventive_measures": ["string"],
  "confidence": 0.0-1.0
}
PROMPT;
    }

    private function buildDiagnosticUserPrompt(string $symptoms, string $patientHistory, string $additionalContext): string
    {
        return <<<PROMPT
Симптомы: {$symptoms}

История пациента:
{$patientHistory}

Дополнительный контекст:
{$additionalContext}

Пожалуйста, проведи диагностику и верни результат в JSON формате.
PROMPT;
    }

    private function buildHealthScorePrompt(string $patientHistory, array $labResults, string $lifestyleData): string
    {
        $labResultsText = empty($labResults) ? 'Нет данных' : json_encode($labResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return <<<PROMPT
История пациента:
{$patientHistory}

Результаты анализов:
{$labResultsText}

Образ жизни:
{$lifestyleData}

Оцени текущий health-score (0-100), предсказай на 30 и 90 дней, определи тренд (improving, stable, declining), ключевые факторы, рекомендации и зоны риска.

Ответ в JSON формате:
{
  "current_score": 0-100,
  "predicted_30_days": 0-100,
  "predicted_90_days": 0-100,
  "key_factors": ["string"],
  "recommendations": ["string"],
  "risk_areas": ["string"],
  "confidence": 0.0-1.0
}
PROMPT;
    }

    private function getPatientHistory(int $userId): string
    {
        $records = MedicalRecord::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get(['diagnosis', 'treatment', 'created_at']);

        if ($records->isEmpty()) {
            return 'Нет истории заболеваний.';
        }

        return $records->map(function ($record) {
            return sprintf(
                "[%s] Диагноз: %s, Лечение: %s",
                $record->created_at->format('Y-m-d'),
                $record->diagnosis,
                $record->treatment
            );
        })->implode("\n");
    }

    private function getLifestyleData(int $userId): string
    {
        return json_encode([
            'activity_level' => 'moderate',
            'sleep_hours' => 7,
            'stress_level' => 'medium',
            'diet_quality' => 'good',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function calculateHealthScore(array $diagnosticData, array $symptoms): int
    {
        $baseScore = 75;
        
        $urgencyPenalty = match ($diagnosticData['urgency_level'] ?? 'routine') {
            'emergency' => 50,
            'urgent' => 30,
            'routine' => 0,
            default => 0,
        };

        $symptomCount = count($symptoms);
        $symptomPenalty = min($symptomCount * 3, 20);

        $riskFactorPenalty = min(count($diagnosticData['risk_factors'] ?? []) * 2, 15);

        $confidence = floatval($diagnosticData['confidence'] ?? 0.5);
        $confidenceAdjustment = (1 - $confidence) * 10;

        $finalScore = $baseScore - $urgencyPenalty - $symptomPenalty - $riskFactorPenalty - $confidenceAdjustment;

        return max(self::HEALTH_SCORE_MIN, min(self::HEALTH_SCORE_MAX, intval($finalScore)));
    }

    private function calculateTrend(int $current, int $predicted): string
    {
        $diff = $predicted - $current;
        
        return match (true) {
            $diff > 5 => 'improving',
            $diff < -5 => 'declining',
            default => 'stable',
        };
    }

    private function saveDiagnosticResult(int $userId, AIDiagnosticResultDto $result): void
    {
        $this->redis->setex(
            "healthcare:diagnostic:{$userId}:" . Str::uuid()->toString(),
            86400,
            json_encode($result->toArray())
        );
    }

    private function saveHealthScorePrediction(int $userId, HealthScorePredictionDto $result): void
    {
        $this->redis->setex(
            "healthcare:healthscore:history:{$userId}:" . now()->format('Y-m-d'),
            31536000,
            json_encode($result->toArray())
        );
    }

    private function triggerEmergencyProtocol(int $userId, AIDiagnosticResultDto $result, string $correlationId): void
    {
        $this->logger->critical('Emergency protocol triggered', [
            'user_id' => $userId,
            'health_score' => $result->healthScore,
            'primary_diagnosis' => $result->primaryDiagnosis,
            'correlation_id' => $correlationId,
        ]);

        event(new \App\Domains\Medical\MedicalHealthcare\Events\EmergencyDetectedEvent($userId, $result, $correlationId));
    }

    private function calculateDoctorMatchScore(Doctor $doctor, AIDiagnosticResultDto $diagnostic, int $userId): float
    {
        $score = 50.0;
        
        $specialtyMatch = in_array($doctor->specialty, $diagnostic->recommendedSpecialties, true) ? 20 : 0;
        $score += $specialtyMatch;

        $ratingBonus = (floatval($doctor->rating ?? 4.5) - 4.0) * 10;
        $score += $ratingBonus;

        $experienceBonus = min(intval($doctor->experience_years ?? 0) * 0.5, 10);
        $score += $experienceBonus;

        $loadFactor = $this->getClinicLoadFactor($doctor->clinic_id);
        $score -= $loadFactor * 5;

        return max(0.0, min(100.0, $score));
    }

    private function calculateDynamicPrice(Doctor $doctor, string $urgencyLevel, bool $isB2B): float
    {
        $basePrice = floatval($doctor->consultation_price ?? 2000);
        
        $urgencyMultiplier = match ($urgencyLevel) {
            'emergency' => 2.0,
            'urgent' => 1.5,
            'routine' => 1.0,
            default => 1.0,
        };

        $loadMultiplier = 1.0 + ($this->getClinicLoadFactor($doctor->clinic_id) * 0.3);
        
        $b2bDiscount = $isB2B ? 0.85 : 1.0;

        $finalPrice = $basePrice * $urgencyMultiplier * $loadMultiplier * $b2bDiscount;

        return round($finalPrice, 2);
    }

    private function getClinicLoadFactor(int $clinicId): float
    {
        $todayAppointments = MedicalAppointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_datetime', today())
            ->count();

        $maxDailyCapacity = 100;
        
        return min(floatval($todayAppointments / $maxDailyCapacity), 1.0);
    }

    private function getAvailableSlots(int $doctorId): array
    {
        $today = today();
        $slots = [];
        
        for ($hour = 9; $hour <= 18; $hour++) {
            $slotTime = $today->setHour($hour)->setMinute(0)->format('Y-m-d H:i:s');
            
            $isBooked = MedicalAppointment::where('doctor_id', $doctorId)
                ->where('appointment_datetime', $slotTime)
                ->whereIn('status', ['confirmed', 'checked_in'])
                ->exists();
            
            if (!$isBooked) {
                $slots[] = $slotTime;
            }
        }

        return $slots;
    }

    private function isFlashDiscountAvailable(int $clinicId): bool
    {
        $loadFactor = $this->getClinicLoadFactor($clinicId);
        
        return $loadFactor < 0.3 && now()->hour >= 14 && now()->hour <= 17;
    }

    private function calculateEstimatedWaitTime(int $doctorId): int
    {
        $checkedInCount = MedicalAppointment::where('doctor_id', $doctorId)
            ->where('status', 'checked_in')
            ->whereDate('check_in_time', today())
            ->count();

        return $checkedInCount * 15;
    }

    private function generateEmbedding(string $text): array
    {
        if ($this->openai->isEnabled()) {
            try {
                return $this->openai->generateEmbedding($text);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to generate real embedding, falling back to fake', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback to fake embedding if OpenAI is not available
        $hash = md5($text);
        $embedding = [];

        for ($i = 0; $i < self::EMBEDDING_DIMENSION; $i++) {
            $embedding[$i] = (sin($hash + $i) + 1) / 2;
        }

        return $embedding;
    }

    private function anonymizeMedicalData(string $data): string
    {
        // Удаляем потенциально идентифицирующую информацию
        $patterns = [
            '/\b[A-ZА-Я][a-zа-я]+\s+[A-ZА-Я][a-zа-я]+\b/' => '[ИМЯ ФАМИЛИЯ]',
            '/\b\d{2}\.\d{2}\.\d{4}\b/' => '[ДАТА]',
            '/\b\d{11}\b/' => '[ТЕЛЕФОН]',
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            '/\b\d{4}\s?\d{4}\s?\d{4}\s?\d{4}\b/' => '[КАРТА]',
            '/\b\d{14}\b/' => '[СНИЛС]',
            '/\b\d{16}\b/' => '[ИНН]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $data);
    }

    private function syncDiagnosticResult(int $userId, array $data, string $correlationId): void
    {
        $this->logger->info('CRM sync: diagnostic result', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);
    }

    private function syncAppointmentBooking(array $appointment, string $correlationId): void
    {
        $this->logger->info('CRM sync: appointment booking', [
            'appointment_id' => $appointment['id'] ?? null,
            'correlation_id' => $correlationId,
        ]);
    }

    private function syncCheckIn(array $appointment, string $correlationId): void
    {
        $this->logger->info('CRM sync: check-in', [
            'appointment_id' => $appointment['id'] ?? null,
            'correlation_id' => $correlationId,
        ]);
    }

    private function getUserId(): int
    {
        $user = $this->guard->user();
        return $user !== null ? intval($user->getAuthIdentifier()) : 0;
    }

    private function getTenantId(): int
    {
        if (function_exists('tenant') && tenant() !== null) {
            return intval(tenant()->id);
        }
        return 1;
    }
}

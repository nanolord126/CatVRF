<?php

declare(strict_types=1);

namespace App\Domains\Medical\Services;

use App\Domains\Medical\Models\Doctor;
use App\Domains\Medical\Models\MedicalService;
use App\Domains\Medical\Models\Clinic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Client as OpenAI;

/**
 * КАНОН 2026: AI Конструктор здоровья (AI Health Optimizer / Constructor).
 * Слой 3: Бизнес-логика / AI / Вертикаль Medical.
 */
final readonly class AIHealthConstructorService
{
    /**
     * Конструктор: подбор врача и анализов по симптомам.
     */
    public function matchSymptomToService(string $symptoms, int $tenantId, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string)Str::uuid();

        try {
            // 1. Анализируем симптомы через AI (эмуляция или OpenAI если настроено)
            // $analysis = $this->openai->chat()->create([
            //     'model' => 'gpt-4o',
            //     'messages' => [['role' => 'user', 'content' => $symptoms]]
            // ]);
            
            // 2. Имитируем анализ (Layer 3 logic)
            $suggestedSpecialization = 'Therapist';
            if (Str::contains(mb_strtolower($symptoms), ['зуб', 'десна'])) $suggestedSpecialization = 'Dentist';
            if (Str::contains(mb_strtolower($symptoms), ['глаз', 'зрение'])) $suggestedSpecialization = 'Ophthalmologist';
            if (Str::contains(mb_strtolower($symptoms), ['сердце', 'давление'])) $suggestedSpecialization = 'Cardiologist';

            // 3. Подбираем врачей в текущем tenant
            $doctors = Doctor::where('tenant_id', $tenantId)
                ->whereJsonContains('specialization', $suggestedSpecialization)
                ->where('status', 'active')
                ->orderByDesc('rating')
                ->limit(3)
                ->get();

            // 4. Подбираем первичные услуги
            $services = MedicalService::where('tenant_id', $tenantId)
                ->where('category_name', 'Primary Care') // Базовая категория
                ->where('is_active', true)
                ->limit(3)
                ->get();

            Log::channel('audit')->info('AI Health Constructor matched symptoms', [
                'symptoms' => $symptoms,
                'matched_specialization' => $suggestedSpecialization,
                'doctors_count' => $doctors->count(),
                'correlation_id' => $correlationId
            ]);

            return [
                'success' => true,
                'matched_specialization' => $suggestedSpecialization,
                'doctors' => $doctors->map(fn($d) => [
                    'id' => $d->id,
                    'full_name' => $d->full_name,
                    'rating' => $d->rating,
                    'price' => $d->consultation_price
                ]),
                'suggested_services' => $services->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'price' => $s->base_price
                ]),
                'caution' => 'This is an AI recommendation. Please consult a professional for medical diagnosis.',
                'correlation_id' => $correlationId
            ];

        } catch (\Throwable $e) {
            Log::channel('audit')->error('AI Health Constructor failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            throw $e;
        }
    }

    /**
     * Анализ фото высыпаний/состояния кожи (Vision API Mock).
     */
    public function analyzeVisionCondition(\Illuminate\Http\UploadedFile $photo, string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string)Str::uuid();
        
        // КРИТИЧНО: Логируем факт анализа фото - это конфиденциальные данные!
        Log::channel('audit')->info('Vision AI Analysis requested for patient photo', [
            'user_id' => auth()->id(),
            'correlation_id' => $correlationId
        ]);

        return [
            'type' => 'dermatology_suggested',
            'severity' => 'low',
            'recommended_specialist' => 'Dermatologist',
            'confidence_score' => 0.82,
            'correlation_id' => $correlationId
        ];
    }
}

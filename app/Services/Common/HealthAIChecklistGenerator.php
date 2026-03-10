<?php

namespace App\Services\Common;

use App\Models\Common\HealthRecommendation;
use App\Models\Clinic\MedicalCard;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;

class HealthAIChecklistGenerator
{
    /**
     * Создать чеклист из рецепта в медицинской карте с помощью ИИ
     */
    public function generateForCard(MedicalCard $card)
    {
        // В реальном приложении отправляем запрос OpenAI Embeddings/Prompt
        // В нашем шаблоне используем имитацию логики формирования чеклиста

        $prescription = $card->prescription; // "Принимать Витамин Д 1 раз в сутки, 30 дней. Массаж спины 1 раз в неделю, 5 недель."
        
        // Пример упрощенной логики парсинга (в 2026 году это делает LLM агент)
        $tasks = [
            [
                'title' => "Прием препарата: Витамин Д",
                'desc' => "Назначено врачом. " . Str::limit($prescription, 50),
                'freq' => 'DAILY',
                'target' => $card->patient_type,
                'target_id' => $card->patient_id,
            ],
            [
                'title' => "Процедура: Массаж",
                'desc' => "Назначено в медкарте #" . $card->id,
                'freq' => 'WEEKLY',
                'target' => $card->patient_type,
                'target_id' => $card->patient_id,
            ]
        ];

        foreach ($tasks as $task) {
            HealthRecommendation::updateOrCreate([
                'user_id' => $card->owner_id ?? $card->patient_id, // Если пациент - человек, берем его ID
                'medical_card_id' => $card->id,
                'title' => $task['title']
            ], [
                'description' => $task['desc'],
                'target_type' => $task['target'],
                'target_id' => $task['target_id'],
                'frequency' => $task['freq'],
                'next_due_date' => now(),
                'is_completed' => false,
                'correlation_id' => Str::uuid(),
            ]);
        }

        return count($tasks);
    }
}

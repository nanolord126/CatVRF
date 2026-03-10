<?php

namespace App\Observers\Clinic;

use App\Models\MedicalCard;
use App\Services\Common\HealthAIChecklistGenerator;
use Illuminate\Support\Facades\Log;

class MedicalCardObserver
{
    public function __construct(
        protected HealthAIChecklistGenerator $checklistGenerator
    ) {}

    /**
     * Обработка после обновления медицинской карты (например, врач добавил рецепт)
     */
    public function updated(MedicalCard $card): void
    {
        if ($card->wasChanged('prescription') && !empty($card->prescription)) {
            Log::info("MedicalCardObserver: Prescription changed for card #{$card->id}. Regenerating AI Checklist.");
            
            try {
                $this->checklistGenerator->generateForCard($card);
            } catch (\Exception $e) {
                Log::error("Failed to generate AI Checklist for card #{$card->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Обработка при создании новой карты с рецептом
     */
    public function created(MedicalCard $card): void
    {
        if (!empty($card->prescription)) {
            $this->checklistGenerator->generateForCard($card);
        }
    }
}

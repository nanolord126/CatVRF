<?php

declare(strict_types=1);

namespace App\Livewire\Psychology;

use App\Domains\Medical\Psychology\Models\Psychologist;
use App\Domains\Medical\Psychology\Services\AI\AITherapyConstructorService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Витрина маркетплейса психологических услуг.
 * Glassmorphism UI, AI Therapy Matcher.
 */
final class PsychologicalShowcase extends Component
{
    use WithPagination;

    public string $search = '';
    public array $aiSymptoms = [];
    public bool $isAiMatching = false;
    public ?array $aiPlan = null;
    
    public ?int $selectedTherapistId = null;

    protected $queryString = ['search'];

    /**
     * Запуск AI-подбора терапии.
     */
    public function startAiMatch(): void
    {
        $this->isAiMatching = true;
        
        $aiService = app(AITherapyConstructorService::class);
        $this->aiPlan = $aiService->generateTherapyPlan([
            'symptoms' => $this->aiSymptoms,
            'user_id' => auth()->id(),
        ], 'frontend-ai-' . now()->timestamp);

        Log::channel('audit')->info('Frontend AI Match triggered', [
            'symptoms' => $this->aiSymptoms,
        ]);

        $this->isAiMatching = false;
    }

    /**
     * Сброс AI результатов.
     */
    public function resetAi(): void
    {
        $this->aiPlan = null;
        $this->aiSymptoms = [];
    }

    /**
     * Выбор терапевта.
     */
    public function selectTherapist(int $id): void
    {
        $this->selectedTherapistId = $id;
        $this->dispatch('therapist-selected', therapistId: $id);
    }

    public function render()
    {
        $query = Psychologist::with(['clinic', 'reviews'])
            ->where('is_available', true);

        if ($this->search) {
            $query->where('full_name', 'like', '%' . $this->search . '%')
                ->orWhere('specialization', 'like', '%' . $this->search . '%');
        }

        return view('livewire.psychology.psychological-showcase', [
            'psychologists' => $query->paginate(12),
        ])->layout('layouts.marketplace');
    }
}

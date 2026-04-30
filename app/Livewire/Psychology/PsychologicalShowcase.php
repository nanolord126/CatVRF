<?php

declare(strict_types=1);

namespace App\Livewire\Psychology;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Support\Collection;

use App\Domains\Psychology\Models\Therapist;
use App\Services\AI\Psychology\SymptomMatcherService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Illuminate\Log\LogManager;

final class PsychologicalShowcase extends Component
{
    protected $queryString = ['search'];

    public string $search = '';

    public array $aiSymptoms = [];

    public bool $isAiMatching = false;

    public ?array $aiPlan = null;

    public ?int $selectedTherapistId = null;

    public function __construct(private readonly ChannelManager $notificationManager,
        private readonly ViewFactory $viewFactory,
        private readonly LogManager $logger,) {}

    public function mount(): void
    {
        // Инициализация, если необходимо
    }

    public function findTherapist(SymptomMatcherService $symptomMatcher): void
    {
        $this->isAiMatching = true;
        $this->aiPlan = null;

        try {
            $this->aiPlan = $symptomMatcher->analyzeSymptoms($this->aiSymptoms);
        } catch (\Exception $e) {
            $this->logger->channel('audit')->error('AI Symptom Matcher failed', [
                'symptoms' => $this->aiSymptoms,
                'error' => $e->getMessage(),
            ]);

            $this->notificationManager->make()
                ->title('Ошибка анализа')
                ->body('Не удалось проанализировать симптомы. Попробуйте позже.')
                ->danger()
                ->send();
        } finally {
            $this->isAiMatching = false;
        }
    }

    public function selectTherapist(int $therapistId): void
    {
        $this->selectedTherapistId = $therapistId;
        $this->dispatch('therapistSelected', $therapistId);
    }

    public function render(): View
    {
        $therapists = Therapist::query()
            ->when($this->search, fn ($query) => $query->where('specialization', 'like', "%{$this->search}%"))
            ->when($this->aiPlan, function ($query) {
                // @phpstan-ignore-next-line
                return $query->whereIn('id', collect($this->aiPlan['recommended_therapists'])->pluck('id'));
            })
            ->paginate(10);

        return $this->viewFactory->make('livewire.psychology.psychological-showcase', [
            'therapists' => $therapists,
        ]);
    }
}

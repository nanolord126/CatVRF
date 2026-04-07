<?php declare(strict_types=1);

namespace App\Livewire\Psychology;

use App\Domains\Psychology\Models\Therapist;
use App\Services\AI\Psychology\SymptomMatcherService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Log\LogManager;

final class PsychologicalShowcase extends Component
{
    public function __construct(
        private readonly LogManager $logger,
    ) {}

    use WithPagination;

    private string $search = '';
    private array $aiSymptoms = [];
    private bool $isAiMatching = false;
    private ?array $aiPlan = null;
    private ?int $selectedTherapistId = null;

    protected $queryString = ['search'];

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

            Notification::make()
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

        return view('livewire.psychology.psychological-showcase', [
            'therapists' => $therapists,
        ]);
    }
}

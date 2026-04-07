<?php declare(strict_types=1);

namespace App\Livewire\Marketplace\LanguageLearning;

use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;

final class LanguageLearningShowcase extends Component
{
    public function __construct(
        private readonly Guard $guard,
    ) {}

    private string $search = '';
        private string $selectedLanguage = '';
        private bool $showAiPanel = false;

        // AI Params
        private string $aiLanguage = 'English';
        private string $aiLevel = 'A0';
        private string $aiGoal = 'Business';
        private int $aiWeeklyHours = 5;
        private ?array $aiResult = null;

        protected $queryString = ['search', 'selectedLanguage'];

        public function toggleAiPanel(): void
        {
            $this->showAiPanel = !$this->showAiPanel;
        }

        public function generateAiPath(): void
        {
            $correlationId = Str::uuid()->toString();

            try {
                $constructor = app(AILearningPathConstructor::class);
                $this->aiResult = $constructor->constructPath([
                    'language' => $this->aiLanguage,
                    'level' => $this->aiLevel,
                    'goal' => $this->aiGoal,
                    'weekly_hours' => $this->aiWeeklyHours,
                ], (int)$this->guard->user()?->tenant_id ?? 0, $correlationId);

                Notification::make()
                    ->title('AI Path Generated')
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('AI Error: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        public function render()
        {
            $courses = LanguageCourse::with(['teacher', 'school'])
                ->where('is_active', true)
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->when($this->selectedLanguage, fn($q) => $q->where('language', $this->selectedLanguage))
                ->orderBy('rating', 'desc')
                ->paginate(12);

            return view('livewire.marketplace.language-learning.showcase', [
                'courses' => $courses,
            ])->layout('layouts.marketplace');
        }
}

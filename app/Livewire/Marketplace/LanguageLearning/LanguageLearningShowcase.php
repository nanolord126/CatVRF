<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace\LanguageLearning;

use AILearningPathConstructor;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Illuminate\Contracts\Auth\Guard;

final class LanguageLearningShowcase extends Component
{
    protected $queryString = ['search', 'selectedLanguage'];

    private readonly string $search = '';

    private readonly string $selectedLanguage = '';

    private readonly bool $showAiPanel = false;

    // AI Params
    private readonly string $aiLanguage = 'English';

    private readonly string $aiLevel = 'A0';

    private readonly string $aiGoal = 'Business';

    private readonly int $aiWeeklyHours = 5;

    private readonly ?array $aiResult = null;

    public function __construct(private readonly AILearningPathConstructor $aILearningPathConstructor,
        private readonly ChannelManager $notificationManager,
        private readonly ViewFactory $viewFactory,
        private readonly Guard $guard,) {}

    public function toggleAiPanel(): void
    {
        $this->showAiPanel = ! $this->showAiPanel;
    }

    public function generateAiPath(): void
    {
        $correlationId = Str::uuid()->toString();

        try {
            $constructor = $this->aILearningPathConstructor /* TODO: inject via constructor DI */ /* TODO: inject via DI */;
            $this->aiResult = $constructor->constructPath([
                'language' => $this->aiLanguage,
                'level' => $this->aiLevel,
                'goal' => $this->aiGoal,
                'weekly_hours' => $this->aiWeeklyHours,
            ], (int) $this->guard->user()?->tenant_id ?? 0, $correlationId);

            $this->notificationManager->make()
                ->title('AI Path Generated')
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->notificationManager->make()
                ->title('AI Error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        $courses = LanguageCourse::with(['teacher', 'school'])
            ->where('is_active', true)
            ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->selectedLanguage, fn ($q) => $q->where('language', $this->selectedLanguage))
            ->orderBy('rating', 'desc')
            ->paginate(12);

        return $this->viewFactory->make('livewire.marketplace.language-learning.showcase', [
            'courses' => $courses,
        ])->layout('layouts.marketplace');
    }
}

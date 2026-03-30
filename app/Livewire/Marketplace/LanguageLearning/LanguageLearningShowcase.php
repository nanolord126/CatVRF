<?php declare(strict_types=1);

namespace App\Livewire\Marketplace\LanguageLearning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LanguageLearningShowcase extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public string $search = '';
        public string $selectedLanguage = '';
        public bool $showAiPanel = false;

        // AI Params
        public string $aiLanguage = 'English';
        public string $aiLevel = 'A0';
        public string $aiGoal = 'Business';
        public int $aiWeeklyHours = 5;
        public ?array $aiResult = null;

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
                ], (int)auth()->user()?->tenant_id ?? 0, $correlationId);

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

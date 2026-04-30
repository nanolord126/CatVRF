<?php

declare(strict_types=1);

namespace App\Services\Personalization\Presentation\Livewire;

use Livewire\Component;
use App\Services\Personalization\PersonalizationService;
use App\Services\Personalization\DTOs\Recommendation;
use Illuminate\Support\Collection;

final class PersonalizedRecommendationsPanel extends Component
{
    public object $subject;
    public string $vertical;
    public int $limit = 8;
    
    public Collection $recommendations;
    public bool $loading = true;
    public bool $showExplanation = false;
    public ?Recommendation $selectedRecommendation = null;
    
    public string $viewMode = 'grid'; // grid, list
    public string $sortOrder = 'score'; // score, name, price

    public function mount(object $subject, string $vertical, int $limit = 8): void
    {
        $this->subject = $subject;
        $this->vertical = $vertical;
        $this->limit = $limit;
        
        $this->loadRecommendations();
    }

    public function loadRecommendations(): void
    {
        $this->loading = true;

        try {
            $service = app(PersonalizationService::class);
            
            if ($this->subject instanceof \App\Models\User) {
                $this->recommendations = $service->recommendForClient(
                    $this->subject,
                    $this->vertical,
                    $this->limit
                );
            } else {
                $this->recommendations = $service->recommendForPet(
                    $this->subject,
                    $this->vertical,
                    $this->limit
                );
            }

            $this->sortRecommendations();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load recommendations');
            $this->recommendations = collect();
        } finally {
            $this->loading = false;
        }
    }

    public function sortRecommendations(): void
    {
        $sorted = match ($this->sortOrder) {
            'score' => $this->recommendations->sortByDesc('score'),
            'name' => $this->recommendations->sortBy('name'),
            'price' => $this->recommendations->sortBy(fn ($r) => $r->explanation['price'] ?? 0),
            default => $this->recommendations->sortByDesc('score'),
        };

        $this->recommendations = $sorted->values();
    }

    public function showRecommendationDetails(int $index): void
    {
        $this->selectedRecommendation = $this->recommendations->get($index);
        $this->showExplanation = true;
    }

    public function hideExplanation(): void
    {
        $this->showExplanation = false;
        $this->selectedRecommendation = null;
    }

    public function recordFeedback(int $itemId, string $action): void
    {
        try {
            $service = app(PersonalizationService::class);
            $service->recordFeedback(
                $this->subject->id,
                $itemId,
                $action,
                ['vertical' => $this->vertical]
            );

            $this->dispatch('feedback-recorded');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to record feedback');
        }
    }

    public function refresh(): void
    {
        $this->loadRecommendations();
    }

    public function toggleViewMode(): void
    {
        $this->viewMode = $this->viewMode === 'grid' ? 'list' : 'grid';
    }

    public function getRecommendationCountProperty(): int
    {
        return $this->recommendations->count();
    }

    public function getHighConfidenceCountProperty(): int
    {
        return $this->recommendations->filter(fn ($r) => $r->isHighConfidence())->count();
    }

    public function getAverageConfidenceProperty(): float
    {
        if ($this->recommendations->isEmpty()) {
            return 0.0;
        }

        return $this->recommendations->avg('confidence');
    }

    public function getVerticalLabelProperty(): string
    {
        return match ($this->vertical) {
            'beauty' => 'Beauty & Cosmetics',
            'grooming' => 'Pet Grooming',
            'food' => 'Food & Dining',
            'fitness' => 'Fitness & Wellness',
            default => ucfirst($this->vertical),
        };
    }

    public function getConfidenceColorProperty(string $confidence): string
    {
        return match (true) {
            $confidence >= 0.8 => 'green',
            $confidence >= 0.5 => 'yellow',
            default => 'gray',
        };
    }

    public function getScorePercentageProperty(float $score): int
    {
        return (int) round($score * 100);
    }

    public function render()
    {
        return view('personalization::livewire.personalized-recommendations-panel');
    }
}

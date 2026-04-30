<?php

declare(strict_types=1);

namespace Modules\Fashion\Application\Livewire;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Modules\Fashion\Domain\Repositories\TexturePackRepositoryInterface;
use Modules\Fashion\Domain\ValueObjects\TextureGenerationStatus;
use Modules\Fashion\Domain\ValueObjects\TextureType;

class TextureGenerationMonitor extends Component
{
    public array $statistics = [];
    public array $recentGenerations = [];
    public array $failedGenerations = [];
    public string $selectedStatus = 'all';
    public string $selectedMaterialType = 'all';
    public bool $autoRefresh = true;
    public int $refreshInterval = 5;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $repository = app(TexturePackRepositoryInterface::class);

        $this->statistics = $repository->getStatistics();

        $this->loadRecentGenerations($repository);
        $this->loadFailedGenerations($repository);
    }

    private function loadRecentGenerations(TexturePackRepositoryInterface $repository): void
    {
        $query = $repository->findByDateRange(
            new \DateTimeImmutable('-24 hours'),
            new \DateTimeImmutable(),
        );

        if ($this->selectedStatus !== 'all') {
            $status = TextureGenerationStatus::from($this->selectedStatus);
            $query = $query->filter(fn($pack) => $pack->getStatus() === $status);
        }

        if ($this->selectedMaterialType !== 'all') {
            $materialType = TextureType::from($this->selectedMaterialType);
            $query = $query->filter(fn($pack) => $pack->getMaterialType() === $materialType);
        }

        $this->recentGenerations = $query
            ->map(fn($pack) => [
                'uuid' => $pack->getUuid(),
                'name' => $pack->getName(),
                'material_type' => $pack->getMaterialType()->value,
                'status' => $pack->getStatus()->value,
                'status_label' => $pack->getStatus()->getLabel(),
                'status_color' => $pack->getStatus()->getColor(),
                'status_icon' => $pack->getStatus()->getIcon(),
                'progress' => $pack->getStatus()->getProgress(),
                'created_at' => $pack->getCreatedAt()->format('Y-m-d H:i:s'),
                'completed_at' => $pack->getCompletedAt()?->format('Y-m-d H:i:s'),
                'generation_time' => $pack->getCompletedAt()
                    ? $pack->getCompletedAt()->getTimestamp() - $pack->getCreatedAt()->getTimestamp()
                    : null,
                'error_message' => $pack->getErrorMessage(),
            ])
            ->sortByDesc('created_at')
            ->take(50)
            ->values()
            ->toArray();
    }

    private function loadFailedGenerations(TexturePackRepositoryInterface $repository): void
    {
        $failed = $repository->findWithError(20);

        $this->failedGenerations = $failed
            ->map(fn($pack) => [
                'uuid' => $pack->getUuid(),
                'name' => $pack->getName(),
                'material_type' => $pack->getMaterialType()->value,
                'error_message' => $pack->getErrorMessage(),
                'retry_count' => $pack->getGenerationMetadata()['retry_count'] ?? 0,
                'created_at' => $pack->getCreatedAt()->format('Y-m-d H:i:s'),
                'can_retry' => $pack->getStatus()->allowsRetry(),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->toArray();
    }

    public function retryGeneration(string $uuid): void
    {
        try {
            $service = app(\Modules\Fashion\Application\Services\TextureGenerationService::class);
            $service->retryFailedGeneration($uuid);

            Notification::make()
                ->title('Retry initiated')
                ->body('Texture generation retry has been queued')
                ->success()
                ->send();

            $this->loadData();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Retry failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelGeneration(string $uuid): void
    {
        try {
            $service = app(\Modules\Fashion\Application\Services\TextureGenerationService::class);
            $service->cancelGeneration($uuid);

            Notification::make()
                ->title('Generation cancelled')
                ->body('Texture generation has been cancelled')
                ->success()
                ->send();

            $this->loadData();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Cancellation failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function refresh(): void
    {
        $this->loadData();
    }

    public function updatedAutoRefresh(): void
    {
        // Auto-refresh will be handled by the Livewire polling mechanism
    }

    public function getPendingCountProperty(): int
    {
        return $this->statistics['by_status'][TextureGenerationStatus::PENDING->value]['count'] ?? 0;
    }

    public function getProcessingCountProperty(): int
    {
        return $this->statistics['by_status'][TextureGenerationStatus::PROCESSING->value]['count'] ?? 0;
    }

    public function getCompletedCountProperty(): int
    {
        return $this->statistics['by_status'][TextureGenerationStatus::COMPLETED->value]['count'] ?? 0;
    }

    public function getFailedCountProperty(): int
    {
        return $this->statistics['by_status'][TextureGenerationStatus::FAILED->value]['count'] ?? 0;
    }

    public function getSuccessRateProperty(): float
    {
        return $this->statistics['success_rate'] ?? 0;
    }

    public function getAverageGenerationTimeProperty(): float
    {
        return $this->statistics['average_generation_time_seconds'] ?? 0;
    }

    public function render()
    {
        return view('fashion::livewire.texture-generation-monitor', [
            'statistics' => $this->statistics,
            'recentGenerations' => $this->recentGenerations,
            'failedGenerations' => $this->failedGenerations,
            'pendingCount' => $this->pendingCount,
            'processingCount' => $this->processingCount,
            'completedCount' => $this->completedCount,
            'failedCount' => $this->failedCount,
            'successRate' => $this->successRate,
            'averageGenerationTime' => $this->averageGenerationTime,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
        ]);
    }

    public function getFiltersForm(): array
    {
        return [
            Section::make('Filters')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('selectedStatus')
                                ->label('Status')
                                ->options([
                                    'all' => 'All',
                                    'pending' => 'Pending',
                                    'processing' => 'Processing',
                                    'validating' => 'Validating',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed',
                                ])
                                ->default('all')
                                ->live()
                                ->afterStateUpdated(fn() => $this->loadData()),

                            Select::make('selectedMaterialType')
                                ->label('Material Type')
                                ->options([
                                    'all' => 'All',
                                    ...collect(TextureType::cases())->mapWithKeys(fn($type) => [$type->value => ucfirst($type->value)]),
                                ])
                                ->default('all')
                                ->live()
                                ->afterStateUpdated(fn() => $this->loadData()),
                        ]),

                    Toggle::make('autoRefresh')
                        ->label('Auto Refresh')
                        ->default(true)
                        ->live(),

                    TextInput::make('refreshInterval')
                        ->label('Refresh Interval (seconds)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->default(5)
                        ->live()
                        ->afterStateUpdated(fn() => $this->loadData()),
                ])
                ->collapsible(),
        ];
    }

    public function getStatisticsCards(): array
    {
        return [
            [
                'title' => 'Total Generations',
                'value' => $this->statistics['total'] ?? 0,
                'icon' => 'heroicon-o-cube',
                'color' => 'gray',
            ],
            [
                'title' => 'Pending',
                'value' => $this->pendingCount,
                'icon' => 'heroicon-o-clock',
                'color' => 'gray',
            ],
            [
                'title' => 'Processing',
                'value' => $this->processingCount,
                'icon' => 'heroicon-o-cpu-chip',
                'color' => 'blue',
            ],
            [
                'title' => 'Completed',
                'value' => $this->completedCount,
                'icon' => 'heroicon-o-check-circle',
                'color' => 'green',
            ],
            [
                'title' => 'Failed',
                'value' => $this->failedCount,
                'icon' => 'heroicon-o-x-circle',
                'color' => 'red',
            ],
            [
                'title' => 'Success Rate',
                'value' => round($this->successRate, 1) . '%',
                'icon' => 'heroicon-o-chart-bar',
                'color' => $this->successRate >= 80 ? 'green' : ($this->successRate >= 60 ? 'yellow' : 'red'),
            ],
            [
                'title' => 'Avg. Generation Time',
                'value' => round($this->averageGenerationTime, 1) . 's',
                'icon' => 'heroicon-o-clock',
                'color' => 'blue',
            ],
        ];
    }

    public function getMaterialTypeDistribution(): array
    {
        $repository = app(TexturePackRepositoryInterface::class);
        $distribution = $repository->getCountByMaterialType();

        $total = array_sum($distribution);

        return collect($distribution)
            ->map(fn($count, $type) => [
                'type' => ucfirst($type),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }

    public function getDailyStatistics(): array
    {
        $repository = app(TexturePackRepositoryInterface::class);
        return $repository->getDailyStatistics(7);
    }

    public function getStuckGenerationsCount(): int
    {
        $repository = app(TexturePackRepositoryInterface::class);
        return $repository->findStuckInProcessing()->count();
    }

    public function cleanupOldFailed(): void
    {
        try {
            $repository = app(TexturePackRepositoryInterface::class);
            $deleted = $repository->cleanupOldFailed(30);

            Notification::make()
                ->title('Cleanup completed')
                ->body("Deleted {$deleted} old failed generations")
                ->success()
                ->send();

            $this->loadData();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Cleanup failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getStorageUsage(): array
    {
        $repository = app(TexturePackRepositoryInterface::class);
        return $repository->getStorageUsage();
    }
}

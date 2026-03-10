<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Services\Analytics\ConsumerBehaviorAIService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use App\Models\Analytics\ConsumerBehaviorLog;

class ConsumerBehaviorAnalyticsDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string $view = 'filament.tenant.pages.consumer-behavior-analytics-dashboard';
    protected static ?string $title = 'AI Behavior Analytics 2026';
    protected static ?string $navigationGroup = 'AI & Insights';

    public ?int $selectedUserId = null;
    public array $aiInsights = [];
    public bool $isCalculated = false;

    /**
     * Define the data table for recent activities/logs.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(ConsumerBehaviorLog::query()->latest())
            ->columns([
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('event_type')->badge()->color(fn (string $state): string => match ($state) {
                    'purchase' => 'success',
                    'view_product' => 'info',
                    'taxi_cancel' => 'danger',
                    default => 'gray',
                }),
                TextColumn::make('entity_type')->label('Resource')->size('xs'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    /**
     * Define the form to select a user for deep analysis.
     */
    protected function getFormSchema(): array
    {
        return [
            Section::make('Deep Customer AI Analysis')
                ->description('Select a customer to generate real-time predictive insights (Churn, LTV, RFM).')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('selectedUserId')
                                ->label('Select Customer')
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live(),
                        ]),
                    
                    Grid::make(2)
                        ->visible(fn () => $this->isCalculated)
                        ->schema([
                            Placeholder::make('segment')
                                ->label('RFM Segment')
                                ->content(fn () => $this->aiInsights['rfm']['segment'] ?? 'N/A'),
                            Placeholder::make('churn_probability')
                                ->label('Churn Risk')
                                ->content(fn () => ($this->aiInsights['churn_probability'] * 100) . '%'),
                            Placeholder::make('suggested_offer')
                                ->label('AI Next-Best-Offer')
                                ->content(fn () => $this->aiInsights['suggested_offer'] ?? 'N/A'),
                            Placeholder::make('ai_advice')
                                ->label('AI Reasoning & Advice')
                                ->content(fn () => $this->aiInsights['advice'] ?? 'N/A'),
                        ]),
                ])
                ->footerActions([
                    \Filament\Forms\Components\Actions\Action::make('generateInsights')
                        ->label('Run AI Prediction')
                        ->icon('heroicon-o-sparkles')
                        ->action('runAnalysis'),
                ]),
        ];
    }

    /**
     * Execute the analysis via the service.
     */
    public function runAnalysis(ConsumerBehaviorAIService $service): void
    {
        if (!$this->selectedUserId) return;

        $user = User::find($this->selectedUserId);
        if ($user) {
            $this->aiInsights = $service::generatePersonalizedAIOffer($user);
            $this->isCalculated = true;
            
            \Filament\Notifications\Notification::make()
                ->title('AI Analysis Complete')
                ->success()
                ->send();
        }
    }
}

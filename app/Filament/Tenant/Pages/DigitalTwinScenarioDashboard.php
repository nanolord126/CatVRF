<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Services\AI\Simulations\DigitalTwinBusinessEngine;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Slider;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class DigitalTwinScenarioDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-variable';
    protected static string $view = 'filament.tenant.pages.digital-twin-scenario-dashboard';
    protected static ?string $title = 'Digital Twin Scenario 2026';
    protected static ?string $navigationGroup = 'AI & Insights';

    public float $priceChange = 1.0;
    public int $staffCountChange = 0;
    public string $vertical = 'taxi';
    public array $simulationResult = [];
    public bool $isCalculated = false;

    protected function getFormSchema(): array
    {
        return [
            Section::make('Simulation Parameters')
                ->description('Modify business triggers to see how the "Digital Twin" of your organization reacts 30 days into the future.')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('vertical')
                                ->label('Select Vertical')
                                ->options([
                                    'taxi' => 'Taxi Fleet AI',
                                    'food' => 'Restaurant Chain KDS',
                                    'clinic' => 'Medical Clinic Network',
                                ])
                                ->default('taxi')
                                ->required(),
                            Slider::make('priceChange')
                                ->label('Price Tariff Change (1.0 = Base)')
                                ->min(0.5)
                                ->max(2.0)
                                ->step(0.05)
                                ->default(1.0)
                                ->live(),
                            TextInput::make('staffCountChange')
                                ->label('Headcount Change (Person +/-)')
                                ->numeric()
                                ->step(1)
                                ->default(0)
                        ]),
                ])
                ->footerActions([
                    \Filament\Forms\Components\Actions\Action::make('runSimulation')
                        ->label('Run Simulation')
                        ->icon('heroicon-o-play-circle')
                        ->action('runSimulation'),
                ]),

            Section::make('Digital Twin Predictions (Future 30 Days)')
                ->visible(fn () => $this->isCalculated)
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('monthly_rev')
                                ->label('Monthly Revenue Projection')
                                ->content(fn () => '$' . number_format($this->simulationResult['predicted_monthly_revenue'] ?? 0, 2))
                                ->extraAttributes(['class' => 'text-success font-bold text-lg']),
                            Placeholder::make('staff_load')
                                ->label('Staff Utilization %')
                                ->content(fn () => ($this->simulationResult['staff_load_projection'] ?? 0) . '%')
                                ->color(fn ($state) => (float)$state > 90.0 ? 'danger' : 'success'),
                            Placeholder::make('churn_risk')
                                ->label('Churn Risk Delta')
                                ->content(fn () => ($this->simulationResult['churn_risk_delta'] * 100) . '%'),
                        ]),
                    Placeholder::make('summary')
                        ->label('AI Insight Summary')
                        ->content(fn () => $this->simulationResult['summary'] ?? 'N/A'),
                ]),
        ];
    }

    public function runSimulation(DigitalTwinBusinessEngine $engine): void
    {
        $this->simulationResult = $engine->runScenario($this->vertical, [
            'tariff_change' => $this->priceChange,
            'staff_count_change' => (int)$this->staffCountChange,
        ]);

        $this->isCalculated = true;

        \Filament\Notifications\Notification::make()
            ->title('Digital Twin Simulation Complete')
            ->success()
            ->send();
    }
}

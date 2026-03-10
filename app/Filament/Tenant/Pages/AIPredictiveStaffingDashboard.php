<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Services\Common\AI\StaffPredictiveEngine;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Carbon\Carbon;
use Filament\Support\Colors\Color;

class AIPredictiveStaffingDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Human Resources 2.0';
    protected static ?string $title = 'Predictive Capacity Planner AI';
    protected static string $view = 'filament.tenant.pages.ai-predictive-staffing-dashboard';

    public ?string $vertical = 'Sports';
    public ?string $targetDate = null;
    public array $predictionData = [];
    public bool $isCalculated = false;

    public function mount(): void
    {
        $this->targetDate = Carbon::tomorrow()->toDateString();
        $this->form->fill([
            'vertical' => 'Sports',
            'targetDate' => $this->targetDate,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Forecast Parameters')
                ->description('Simulate future staffing needs based on demand influx.')
                ->schema([
                    Select::make('vertical')
                        ->options([
                            'Sports' => 'Gyms & Training (Coaches)',
                            'Education' => 'Academy & Courses (Tutors)',
                            'Events' => 'Security & Logistics',
                        ])
                        ->default('Sports')
                        ->required(),
                    DatePicker::make('targetDate')
                        ->label('Forecast Target Date')
                        ->default(Carbon::tomorrow())
                        ->required(),
                ])
                ->footerActions([
                    \Filament\Forms\Components\Actions\Action::make('forecast')
                        ->label('Run AI Prediction')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->action('runSimulation')
                ]),
        ];
    }

    public function runSimulation(): void
    {
        $engine = new StaffPredictiveEngine();
        $date = Carbon::parse($this->targetDate);
        
        $this->predictionData = $engine->forecastStaffing($this->vertical, $date);
        $this->isCalculated = true;
        
        \Filament\Notifications\Notification::make()
            ->title('Prediction Complete')
            ->body("AI identified a " . ($this->predictionData['risk']) . " risk for the selected date.")
            ->success()
            ->send();
    }
}

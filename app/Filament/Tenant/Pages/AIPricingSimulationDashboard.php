<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Services\AI\Pricing\DynamicAIPricingEngine;
use App\Services\Analytics\ConsumerBehaviorAIService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;

class AIPricingSimulationDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'filament.tenant.pages.ai-pricing-simulation-dashboard';
    protected static ?string $title = 'AI Dynamic Pricing 2026';
    protected static ?string $navigationGroup = 'AI & Insights';

    public ?int $userId = null;
    public ?string $vertical = 'taxi';
    public ?float $basePrice = 100.00;
    public ?string $device = 'ios';
    public ?float $finalPrice = null;
    public array $features = [];
    public ?string $persona = 'N/A';

    public function table(Table $table): Table
    {
        return $table
            ->query(DB::table('dynamic_price_calculations')->latest())
            ->columns([
                TextColumn::make('user_id')->label('User ID')->sortable(),
                TextColumn::make('vertical')->badge(),
                TextColumn::make('base_price')->money('USD'),
                TextColumn::make('final_price')->money('USD')->color('success'),
                TextColumn::make('applied_multiplier')->label('Mult.')->numeric(4),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Real-time Dynamic Pricing Simulator')
                ->description('Simulate how AI adjusts prices based on the customer\'s profile, device, and cross-vertical loyalty.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('userId')
                                ->label('Target Customer')
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live(),
                            TextInput::make('basePrice')
                                ->label('Base Service Price')
                                ->numeric()
                                ->prefix('$')
                                ->default(100.00)
                                ->required(),
                            Select::make('vertical')
                                ->label('Vertical')
                                ->options([
                                    'taxi' => 'Taxi Ride',
                                    'food' => 'Food Order',
                                    'clinic' => 'Medical Appointment',
                                ])
                                ->default('taxi')
                                ->required(),
                            ToggleButtons::make('device')
                                ->label('User Device Type')
                                ->options([
                                    'ios' => 'iOS App',
                                    'android' => 'Android App',
                                    'desktop' => 'Web Browser',
                                ])
                                ->icons([
                                    'ios' => 'heroicon-o-device-phone-mobile',
                                    'android' => 'heroicon-o-device-phone-mobile',
                                    'desktop' => 'heroicon-o-computer-desktop',
                                ])
                                ->default('ios')
                                ->inline()
                                ->required(),
                        ]),
                ])
                ->footerActions([
                    \Filament\Forms\Components\Actions\Action::make('calculate')
                        ->label('Run AI Price Simulation')
                        ->icon('heroicon-o-sparkles')
                        ->action('simulatePrice'),
                ]),

            Section::make('AI Decision Breakdown')
                ->visible(fn () => $this->finalPrice !== null)
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Placeholder::make('persona_display')
                                ->label('AI Customer Persona')
                                ->content(fn () => $this->persona),
                            Placeholder::make('final_price_display')
                                ->label('AI Calculated Price')
                                ->content(fn () => '$' . number_format($this->finalPrice, 2))
                                ->extraAttributes(['class' => 'text-success font-bold text-lg']),
                            Placeholder::make('total_multiplier')
                                ->label('Total Multiplier')
                                ->content(fn () => round(($this->finalPrice / $this->basePrice), 4) . 'x'),
                        ]),
                    Placeholder::make('features_list')
                        ->label('Applied Multipliers & Logic')
                        ->content(fn () => collect($this->features)->map(fn ($v, $k) => "{$k}: {$v}x")->implode(' | ')),
                ]),
        ];
    }

    public function simulatePrice(DynamicAIPricingEngine $engine, ConsumerBehaviorAIService $analytics): void
    {
        if (!$this->userId) return;

        $user = User::find($this->userId);
        $rfm = $analytics->calculateRFM($user);

        $result = $engine->calculateFinalPrice($user, $this->vertical, $this->basePrice, [
            'device_os' => $this->device,
            'rfm_segment' => $rfm['segment'],
            'cross_vertical_active' => true, // Example
            'global_surge' => 1.15, // Simulate peak hour
        ]);

        $this->finalPrice = $result['final_price'];
        $this->features = $result['features'];

        $profile = DB::table('customer_ai_pricing_profiles')->where('user_id', $user->id)->first();
        $this->persona = $profile->persona_tag ?? 'New Prospect';

        \Filament\Notifications\Notification::make()
            ->title('AI Dynamic Pricing Calculated')
            ->success()
            ->send();
    }
}

<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Services\Common\Loyalty\CrossVerticalLoyaltyEngine;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;

class EcosystemRewardsDashboard extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static string $view = 'filament.tenant.pages.ecosystem-rewards-dashboard';
    protected static ?string $title = 'Ecosystem Rewards 2026';
    protected static ?string $navigationGroup = 'Financial Management';

    public ?int $selectedUserId = null;
    public ?float $balance = 0.0;
    public ?string $tier = 'Standard';

    /**
     * Recent V-Coins Transactions Table.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(DB::table('loyalty_transactions')->latest())
            ->columns([
                TextColumn::make('user_id')->label('User ID')->sortable(),
                TextColumn::make('vertical')->badge(),
                TextColumn::make('amount')
                    ->label('V-Coins')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('type')->label('Action'),
                TextColumn::make('reason'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Customer Reward Overview')
                ->description('View and manage the cross-vertical V-Coins balance for any ecosystem user.')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('selectedUserId')
                                ->label('Select User')
                                ->options(User::pluck('name', 'id'))
                                ->searchable()
                                ->required()
                                ->live(),
                            Placeholder::make('balance_display')
                                ->label('Current V-Coins')
                                ->content(fn () => number_format($this->balance, 2) . ' ◎ V-Coins'),
                            Placeholder::make('tier_display')
                                ->label('Loyalty Tier')
                                ->content(fn () => $this->tier),
                        ]),
                ])
                ->footerActions([
                    \Filament\Forms\Components\Actions\Action::make('refresh')
                        ->label('Update Balances')
                        ->icon('heroicon-o-arrow-path')
                        ->action('refreshData'),
                ]),
        ];
    }

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        if ($this->selectedUserId) {
            $wallet = DB::table('ecosystem_loyalty_wallets')->where('user_id', $this->selectedUserId)->first();
            $this->balance = $wallet->balance ?? 0.0;
            $this->tier = ($wallet->multiplier ?? 1.0) > 1.2 ? 'Platinum' : (($wallet->multiplier ?? 1.0) > 1.1 ? 'Gold' : 'Standard');
        }
    }
}

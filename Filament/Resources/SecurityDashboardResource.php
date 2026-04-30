<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityDashboardResource\Pages;
use App\Models\BruteForceAttempt;
use App\Models\Tenant;
use App\Services\Security\InsiderThreatService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class SecurityDashboardResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->role?->isPlatformAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('id')
                    ->label('Tenant ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('threat_summary.total_threats')
                    ->label('Total Threats')
                    ->sortable()
                    ->getStateUsing(fn ($record) => app(InsiderThreatService::class)->getThreatSummary($record, 30)['total_threats'] ?? 0),

                Tables\Columns\TextColumn::make('threat_summary.critical')
                    ->label('Critical')
                    ->color('danger')
                    ->sortable()
                    ->getStateUsing(fn ($record) => app(InsiderThreatService::class)->getThreatSummary($record, 30)['critical'] ?? 0),

                Tables\Columns\TextColumn::make('threat_summary.high')
                    ->label('High')
                    ->color('warning')
                    ->sortable()
                    ->getStateUsing(fn ($record) => app(InsiderThreatService::class)->getThreatSummary($record, 30)['high'] ?? 0),

                Tables\Columns\TextColumn::make('brute_force_attempts')
                    ->label('Brute Force Attempts')
                    ->sortable()
                    ->getStateUsing(fn ($record) => BruteForceAttempt::where('tenant_id', $record->id)->count()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityDashboards::route('/'),
        ];
    }
}

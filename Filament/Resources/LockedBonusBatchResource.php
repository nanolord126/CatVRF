<?php

declare(strict_types=1);

namespace Filament\Resources;

use App\Domains\Bonuses\Models\LockedBonusBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * LockedBonusBatchResource - Filament resource for managing locked bonus batches
 * 
 * Admin dashboard for CatFloat Rewards system.
 */
final class LockedBonusBatchResource extends Resource
{
    protected static ?string $model = LockedBonusBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Bonuses';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bonus Information')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('original_amount')
                            ->required()
                            ->numeric()
                            ->step(0.01),
                        Forms\Components\TextInput::make('daily_unlock_rate')
                            ->required()
                            ->numeric()
                            ->step(0.0001),
                        Forms\Components\DatePicker::make('vested_until')
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->options([
                                'purchase' => 'Purchase',
                                'referral' => 'Referral',
                                'streak_bonus' => 'Streak Bonus',
                                'campaign' => 'Campaign',
                                'marketplace_sale' => 'Marketplace Sale',
                                'manual' => 'Manual',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant_id')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('original_amount')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remaining_locked')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('daily_unlock_rate')
                    ->percentage(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('vested_until')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'purchase' => 'success',
                        'referral' => 'info',
                        'streak_bonus' => 'warning',
                        'campaign' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'purchase' => 'Purchase',
                        'referral' => 'Referral',
                        'streak_bonus' => 'Streak Bonus',
                        'campaign' => 'Campaign',
                        'marketplace_sale' => 'Marketplace Sale',
                        'manual' => 'Manual',
                    ]),
                Tables\Filters\Filter::make('fully_vested')
                    ->query(fn ($query) => $query->where('remaining_locked', '<=', 0)),
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('remaining_locked', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLockedBonusBatches::route('/'),
            'create' => Pages\CreateLockedBonusBatch::route('/create'),
            'view' => Pages\ViewLockedBonusBatch::route('/{record}'),
            'edit' => Pages\EditLockedBonusBatch::route('/{record}/edit'),
        ];
    }
}

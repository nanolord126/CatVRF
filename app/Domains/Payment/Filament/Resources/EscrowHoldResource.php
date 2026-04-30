<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources;

use App\Domains\Payment\Filament\Resources\EscrowHoldResource\Pages;
use App\Domains\Payment\Models\EscrowHold;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EscrowHoldResource extends Resource
{
    protected static ?string $model = EscrowHold::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Escrow Information')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'held' => 'Held',
                                'partially_released' => 'Partially Released',
                                'released' => 'Released',
                                'canceled' => 'Canceled',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount_kopecks')
                            ->label('Amount (kopecks)')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('released_amount_kopecks')
                            ->label('Released Amount (kopecks)')
                            ->numeric(),
                        Forms\Components\TextInput::make('remaining_amount_kopecks')
                            ->label('Remaining Amount (kopecks)')
                            ->numeric(),
                        Forms\Components\Textarea::make('release_reason')
                            ->label('Release Reason')
                            ->rows(2),
                        Forms\Components\KeyValue::make('release_conditions')
                            ->label('Release Conditions'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('auto_release_at')
                            ->label('Auto Release At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('released_at')
                            ->label('Released At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('canceled_at')
                            ->label('Canceled At')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'held' => 'info',
                        'partially_released' => 'warning',
                        'released' => 'success',
                        'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_kopecks')
                    ->label('Amount (kopecks)')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('remaining_amount_kopecks')
                    ->label('Remaining (kopecks)')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('paymentIntent.uuid')
                    ->label('Payment Intent')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('auto_release_at')
                    ->label('Auto Release')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'held' => 'Held',
                        'partially_released' => 'Partially Released',
                        'released' => 'Released',
                        'canceled' => 'Canceled',
                    ]),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn ($query) => $query->where('auto_release_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEscrowHolds::route('/'),
            'create' => Pages\CreateEscrowHold::route('/create'),
            'view' => Pages\ViewEscrowHold::route('/{record}'),
            'edit' => Pages\EditEscrowHold::route('/{record}/edit'),
        ];
    }
}

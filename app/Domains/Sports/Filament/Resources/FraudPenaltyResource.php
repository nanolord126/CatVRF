<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use App\Domains\Sports\Filament\Resources\FraudPenaltyResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class FraudPenaltyResource extends Resource
{
    protected static ?string $model = \App\Domains\Sports\Models\FraudPenalty::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Sports';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Penalty Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('fraud_type')
                            ->options([
                                'cancellation_fraud' => 'Cancellation Fraud',
                                'no_show_fraud' => 'No-Show Fraud',
                                'booking_pattern_fraud' => 'Booking Pattern Fraud',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('risk_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\Select::make('penalty_type')
                            ->options([
                                'monitoring' => 'Monitoring',
                                'warning' => 'Warning',
                                'booking_restriction_7_days' => 'Booking Restriction (7 days)',
                                'temporary_ban_30_days' => 'Temporary Ban (30 days)',
                                'permanent_ban' => 'Permanent Ban',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('penalty_details')
                            ->rows(5),
                        Forms\Components\TextInput::make('correlation_id')
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fraud_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cancellation_fraud' => 'warning',
                        'no_show_fraud' => 'danger',
                        'booking_pattern_fraud' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (float $state): string => number_format($state * 100, 1) . '%')
                    ->color(fn (float $state): string => $state >= 0.75 ? 'danger' : ($state >= 0.5 ? 'warning' : 'success')),
                Tables\Columns\TextColumn::make('penalty_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'monitoring' => 'info',
                        'warning' => 'warning',
                        'booking_restriction_7_days' => 'warning',
                        'temporary_ban_30_days' => 'danger',
                        'permanent_ban' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('fraud_type')
                    ->options([
                        'cancellation_fraud' => 'Cancellation Fraud',
                        'no_show_fraud' => 'No-Show Fraud',
                        'booking_pattern_fraud' => 'Booking Pattern Fraud',
                    ]),
                Tables\Filters\SelectFilter::make('penalty_type')
                    ->options([
                        'monitoring' => 'Monitoring',
                        'warning' => 'Warning',
                        'booking_restriction_7_days' => 'Booking Restriction',
                        'temporary_ban_30_days' => 'Temporary Ban',
                        'permanent_ban' => 'Permanent Ban',
                    ]),
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
            ]);
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
            'index' => Pages\ListFraudPenalties::route('/'),
            'create' => Pages\CreateFraudPenalty::route('/create'),
            'view' => Pages\ViewFraudPenalty::route('/{record}'),
            'edit' => Pages\EditFraudPenalty::route('/{record}/edit'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hotels\Infrastructure\Models\ExternalBookingReferenceModel;

final class ExternalBookingReferenceResource extends Resource
{
    protected static ?string $model = ExternalBookingReferenceModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Hotels CRM';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Information')
                    ->schema([
                        Forms\Components\Select::make('booking_id')
                            ->relationship('booking', 'confirmation_code')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->options([
                                'booking_com' => 'Booking.com',
                                'ostrovok' => 'Ostrovok',
                                'airbnb' => 'Airbnb',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('external_id')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('external_confirmation_code')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Sync Status')
                    ->schema([
                        Forms\Components\Select::make('sync_status')
                            ->options([
                                'pending' => 'Pending',
                                'synced' => 'Synced',
                                'failed' => 'Failed',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('synced_at'),
                        Forms\Components\Textarea::make('last_sync_error')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get): bool => $get('sync_status') === 'failed'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Raw Data')
                    ->schema([
                        Forms\Components\KeyValue::make('raw_data')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking.confirmation_code')
                    ->label('Booking')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'primary' => 'booking_com',
                        'success' => 'ostrovok',
                        'warning' => 'airbnb',
                    ]),

                Tables\Columns\TextColumn::make('external_id')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('external_confirmation_code')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('sync_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'synced',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('synced_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'booking_com' => 'Booking.com',
                        'ostrovok' => 'Ostrovok',
                        'airbnb' => 'Airbnb',
                    ]),

                Tables\Filters\SelectFilter::make('sync_status')
                    ->options([
                        'pending' => 'Pending',
                        'synced' => 'Synced',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('retry_sync')
                    ->label('Retry Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (ExternalBookingReferenceModel $record): bool => $record->sync_status === 'failed')
                    ->action(function (ExternalBookingReferenceModel $record) {
                        // Retry sync logic would go here
                        $record->update(['sync_status' => 'pending']);
                    }),
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
            'booking' => Tables\Columns\TextColumn::make('booking.confirmation_code'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages\ListExternalBookingReferences::route('/'),
            'create' => \Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages\CreateExternalBookingReference::route('/create'),
            'view' => \Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages\ViewExternalBookingReference::route('/{record}'),
            'edit' => \Modules\Hotels\Filament\Resources\ExternalBookingReferenceResource\Pages\EditExternalBookingReference::route('/{record}/edit'),
        ];
    }
}

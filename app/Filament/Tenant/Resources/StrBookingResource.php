<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\ShortTermRentals\Models\StrBooking;
use App\Domains\ShortTermRentals\Enums\StrBookingStatus;
use App\Domains\ShortTermRentals\Enums\StrDepositStatus;
use App\Domains\ShortTermRentals\Services\StrBookingService;
use App\Filament\Tenant\Resources\StrBookingResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

final class StrBookingResource extends Resource
{
    protected static ?string $model = StrBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Short-Term Rentals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking Details')
                    ->schema([
                        Forms\Components\Select::make('str_apartment_id')
                            ->relationship('apartment', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('check_in_at')
                            ->required(),
                        Forms\Components\DateTimePicker::make('check_out_at')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Finance')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->numeric()
                            ->prefix('RUB')
                            ->required(),
                        Forms\Components\TextInput::make('deposit_amount')
                            ->numeric()
                            ->prefix('RUB')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(StrBookingStatus::class)
                            ->required(),
                        Forms\Components\Select::make('deposit_status')
                            ->options(StrDepositStatus::class)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('apartment.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('check_in_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => StrBookingStatus::Pending->value,
                        'primary' => StrBookingStatus::Confirmed->value,
                        'success' => StrBookingStatus::Completed->value,
                        'danger' => StrBookingStatus::Cancelled->value,
                    ]),
                Tables\Columns\BadgeColumn::make('deposit_status')
                    ->colors([
                        'warning' => StrDepositStatus::Pending->value,
                        'primary' => StrDepositStatus::Held->value,
                        'success' => StrDepositStatus::Released->value,
                        'danger' => StrDepositStatus::Charged->value,
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(StrBookingStatus::class),
                Tables\Filters\SelectFilter::make('deposit_status')
                    ->options(StrDepositStatus::class),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('confirm_arrival')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (StrBooking $record) => $record->status === StrBookingStatus::Confirmed)
                        ->action(function (StrBooking $record) {
                            $record->update(['status' => StrBookingStatus::Completed]);
                            Notification::make()
                                ->title('Arrival Confirmed')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('release_deposit')
                        ->label('Вернуть залог')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->visible(fn (StrBooking $record) => $record->deposit_status === StrDepositStatus::Held)
                        ->action(function (StrBooking $record, StrBookingService $service) {
                            $service->releaseDeposit($record->id);
                            Notification::make()
                                ->title('Залог возвращен клиенту')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('charge_deposit')
                        ->label('Списать залог (ущерб)')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (StrBooking $record) => $record->deposit_status === StrDepositStatus::Held)
                        ->action(function (StrBooking $record, StrBookingService $service) {
                            $service->chargeDepositForDamages($record->id);
                            Notification::make()
                                ->title('Залог списан в пользу владельца')
                                ->danger()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStrBookings::route('/'),
            'create' => Pages\CreateStrBooking::route('/create'),
            'edit' => Pages\EditStrBooking::route('/{record}/edit'),
        ];
    }
}

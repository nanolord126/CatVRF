<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use App\Domains\Sports\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Domains\Sports\Filament\Resources\BookingResource\Pages\ViewBooking;

final class BookingResource extends BaseOptimizedResource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Бронирования';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('class_id')->label('Класс')->relationship('class', 'name')->required(),
            Forms\Components\Select::make('member_id')->label('Член')->relationship('member', 'email')->required(),
            Forms\Components\Select::make('status')->label('Статус')->options([
                'pending' => 'В ожидании',
                'confirmed' => 'Подтверждено',
                'completed' => 'Завершено',
                'cancelled' => 'Отменено',
            ])->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('class.name')->label('Класс')->searchable(),
                Tables\Columns\TextColumn::make('member.email')->label('Член'),
                Tables\Columns\TextColumn::make('status')->label('Статус')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Создано')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'view' => ViewBooking::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()?->id);
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * Relations to eager load for Sports
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}

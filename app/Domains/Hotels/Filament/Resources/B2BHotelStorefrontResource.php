<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * B2BHotelStorefrontResource — Filament-ресурс для управления B2B-витринами Hotels.
 *
 * Предоставляет CRUD-интерфейс для B2B-витрин отелей:
 * - Управление реквизитами (ИНН, компания)
 * - Настройка оптовых скидок и минимальных бронирований
 * - Верификация и активация витрин
 *
 * @package App\Domains\Hotels\Filament\Resources
 */
final class B2BHotelStorefrontResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Hotels B2B';

    protected static ?string $navigationLabel = 'B2B Витрины';

    protected static ?string $modelLabel = 'B2B Витрина';

    protected static ?string $pluralModelLabel = 'B2B Витрины';

    /**
     * Форма создания/редактирования B2B-витрины.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Реквизиты компании')
                ->schema([
                    Forms\Components\TextInput::make('company_name')
                        ->label('Название компании')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('inn')
                        ->label('ИНН')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(12),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->maxLength(1000),
                ]),

            Forms\Components\Section::make('Настройки B2B')
                ->schema([
                    Forms\Components\TextInput::make('wholesale_discount')
                        ->label('Оптовая скидка (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->suffix('%'),

                    Forms\Components\TextInput::make('min_booking_nights')
                        ->label('Минимум ночей бронирования')
                        ->numeric()
                        ->default(3)
                        ->minValue(1),

                    Forms\Components\Toggle::make('is_verified')
                        ->label('Верифицирована')
                        ->disabled(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),
                ]),
        ]);
    }

    /**
     * Таблица списка B2B-витрин.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Компания')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->searchable(),

                Tables\Columns\TextColumn::make('wholesale_discount')
                    ->label('Скидка (%)')
                    ->suffix('%'),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Верификация')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верификация'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    /**
     * Страницы ресурса.
     *
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListB2BHotelStorefronts::route('/'),
            'create' => Pages\CreateB2BHotelStorefront::route('/create'),
            'edit'   => Pages\EditB2BHotelStorefront::route('/{record}/edit'),
        ];
    }

    /**
     * Отладочный массив.
     *
     * @return array<string, mixed>
     */
    public function toDebugArray(): array
    {
        return [
            'class'     => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}

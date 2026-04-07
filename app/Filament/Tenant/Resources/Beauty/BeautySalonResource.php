<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\BeautySalon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * BeautySalonResource — Filament Tenant Panel.
 *
 * Управление салонами красоты в кабинете Tenant.
 * Tenant-scoped: все запросы фильтруются по tenant_id текущего Tenant.
 *
 * CANON 2026: no static facades, no global helpers, correlation_id.
 *
 * @package CatVRF\Filament\Tenant
 * @version 2026.1
 */
final class BeautySalonResource extends Resource
{
    protected static ?string $model = BeautySalon::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Салоны Красоты';

    protected static ?string $navigationGroup = 'Beauty & Wellness';

    protected static ?string $modelLabel = 'Салон красоты';

    protected static ?string $pluralModelLabel = 'Салоны красоты';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Базовые данные салона красоты')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Hidden::make('uuid')
                            ->default(fn (): string => Str::uuid()->toString()),
                        Forms\Components\Hidden::make('correlation_id')
                            ->default(fn (): string => Str::uuid()->toString()),
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn (): ?int => filament()->getTenant()?->id),
                        Forms\Components\Hidden::make('business_group_id')
                            ->default(fn (): ?int => filament()->getTenant()?->active_business_group_id),

                        Forms\Components\TextInput::make('name')
                            ->label('Название салона')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Введите название салона')
                            ->helperText('Отображается в поиске и профиле'),
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Улица, номер дома')
                            ->helperText('Полный адрес салона'),
                        Forms\Components\RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpanFull()
                            ->placeholder('Расскажите о вашем салоне'),
                    ]),

                Forms\Components\Section::make('Контактная информация')
                    ->description('Способы связи с клиентами')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->required()
                            ->placeholder('+7 (XXX) XXX-XX-XX'),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('website')
                            ->label('Веб-сайт')
                            ->url()
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Геолокация')
                    ->description('GPS координаты для карты')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('lat')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.00000001)
                            ->required(),
                        Forms\Components\TextInput::make('lon')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.00000001)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Статус и расписание')
                    ->description('Состояние и время работы')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Салон верифицирован')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                        Forms\Components\TextInput::make('schedule')
                            ->label('Расписание')
                            ->placeholder('Пн-Пт: 09:00-20:00, Сб-Вс: 10:00-18:00')
                            ->helperText('Основной график работы'),
                    ]),

                Forms\Components\Section::make('Комиссии и платежи')
                    ->description('Настройки платформенной комиссии')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('commission_type')
                            ->label('Тип комиссии')
                            ->options([
                                'percent' => 'Процент',
                                'fixed' => 'Фиксированная сумма',
                            ])
                            ->default('percent'),
                        Forms\Components\TextInput::make('commission_value')
                            ->label('Размер комиссии')
                            ->numeric()
                            ->default(14)
                            ->step(0.01),
                        Forms\Components\Select::make('payout_schedule')
                            ->label('График выплат')
                            ->options([
                                'daily' => 'Ежедневно',
                                'weekly' => 'Еженедельно',
                                'biweekly' => 'Раз в две недели',
                                'monthly' => 'Ежемесячно',
                            ])
                            ->default('weekly'),
                    ]),

                Forms\Components\Section::make('Метаданные')
                    ->description('Системная информация')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                        Forms\Components\TextInput::make('review_count')
                            ->label('Количество отзывов')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->copyable()
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(1)
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        (float) $state >= 4.5 => 'success',
                        (float) $state >= 3.5 => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('review_count')
                    ->label('Отзывы')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_value')
                    ->label('Комиссия')
                    ->formatStateUsing(fn ($state): string => "{$state}%"),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верифицирован'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
                Tables\Filters\SelectFilter::make('payout_schedule')
                    ->label('График выплат')
                    ->options([
                        'daily' => 'Ежедневно',
                        'weekly' => 'Еженедельно',
                        'biweekly' => 'Раз в две недели',
                        'monthly' => 'Ежемесячно',
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
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => BeautySalonResource\Pages\ListBeautySalons::route('/'),
            'create' => BeautySalonResource\Pages\CreateBeautySalon::route('/create'),
            'view' => BeautySalonResource\Pages\ViewBeautySalon::route('/{record}'),
            'edit' => BeautySalonResource\Pages\EditBeautySalon::route('/{record}/edit'),
        ];
    }
}

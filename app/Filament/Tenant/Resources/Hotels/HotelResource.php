<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels;

use App\Domains\Hotels\Models\Hotel;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-office';

        protected static ?string $navigationGroup = 'Hotels';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Заполните основные данные о гостинице')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Название гостиницы')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Введите название')
                                ->helperText('Отображается на витрине'),

                            Forms\Components\TextInput::make('phone')
                                ->label('Телефон')
                                ->tel()
                                ->required()
                                ->placeholder('+7 (999) 000-0000')
                                ->copyable(),

                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->placeholder('info@hotel.com')
                                ->copyable(),

                            Forms\Components\TextInput::make('website')
                                ->label('Веб-сайт')
                                ->url()
                                ->nullable()
                                ->placeholder('https://hotel.com'),
                        ]),

                        Forms\Components\RichEditor::make('description')
                            ->label('Описание гостиницы')
                            ->columnSpanFull()
                            ->placeholder('Подробное описание, преимущества, история'),

                        Forms\Components\FileUpload::make('main_photo')
                            ->label('Главное фото')
                            ->image()
                            ->directory('hotels/photos')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Месторасположение')
                    ->description('Адрес, тип, стандарты')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('address')
                                ->label('Адрес')
                                ->required()
                                ->maxLength(500)
                                ->placeholder('Улица, дом, город'),

                            Forms\Components\TextInput::make('city')
                                ->label('Город')
                                ->required()
                                ->placeholder('Москва')
                                ->searchable(),

                            Forms\Components\Select::make('stars')
                                ->label('Звёзды (класс)')
                                ->options([
                                    1 => '⭐ 1 звезда (эконом)',
                                    2 => '⭐⭐ 2 звезды (бюджет)',
                                    3 => '⭐⭐⭐ 3 звезды (стандарт)',
                                    4 => '⭐⭐⭐⭐ 4 звезды (премиум)',
                                    5 => '⭐⭐⭐⭐⭐ 5 звёзд (люкс)',
                                ])
                                ->default(3)
                                ->required(),

                            Forms\Components\Select::make('hotel_type')
                                ->label('Тип гостиницы')
                                ->options([
                                    'hotel' => 'Гостиница',
                                    'hostel' => 'Хостел',
                                    'boutique' => 'Бутик-отель',
                                    'resort' => 'Курортный отель',
                                    'apartment' => 'Апартаменты',
                                    'guest_house' => 'Гостевой дом',
                                ])
                                ->default('hotel')
                                ->required(),

                            Forms\Components\TextInput::make('latitude')
                                ->label('Широта')
                                ->numeric()
                                ->step(0.0001)
                                ->required(),

                            Forms\Components\TextInput::make('longitude')
                                ->label('Долгота')
                                ->numeric()
                                ->step(0.0001)
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Номера и вместимость')
                    ->description('Количество номеров и типы')
                    ->icon('heroicon-m-key')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('total_rooms')
                                ->label('Всего номеров')
                                ->numeric()
                                ->required()
                                ->minValue(1),

                            Forms\Components\TextInput::make('occupied_rooms')
                                ->label('Занято номеров')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Forms\Components\TextInput::make('available_rooms')
                                ->label('Свободно номеров')
                                ->numeric()
                                ->disabled()
                                ->state(fn ($record) => ($record?->total_rooms ?? 0) - ($record?->occupied_rooms ?? 0)),
                        ]),

                        Forms\Components\Repeater::make('room_types')
                            ->label('Типы номеров')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('type_name')
                                        ->label('Название типа')
                                        ->placeholder('Одноместный, Двухместный и т.д.')
                                        ->required(),
                                    Forms\Components\TextInput::make('type_count')
                                        ->label('Количество')
                                        ->numeric()
                                        ->required(),
                                    Forms\Components\TextInput::make('type_price')
                                        ->label('Цена (₽)')
                                        ->numeric()
                                        ->required(),
                                ]),
                            ])
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed(),
                    ]),

                Forms\Components\Section::make('Рейтинг и цены')
                    ->description('Оценки и стоимость номеров')
                    ->icon('heroicon-m-star')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('rating')
                                ->label('Рейтинг (0-5)')
                                ->numeric()
                                ->step(0.1)
                                ->min(0)
                                ->max(5)
                                ->default(4.5)
                                ->disabled(),

                            Forms\Components\TextInput::make('review_count')
                                ->label('Количество отзывов')
                                ->numeric()
                                ->disabled()
                                ->default(0),

                            Forms\Components\TextInput::make('min_price')
                                ->label('Минимальная цена (₽)')
                                ->numeric()
                                ->step(100)
                                ->minValue(0)
                                ->prefix('₽')
                                ->required(),

                            Forms\Components\TextInput::make('max_price')
                                ->label('Максимальная цена (₽)')
                                ->numeric()
                                ->step(100)
                                ->minValue(0)
                                ->prefix('₽')
                                ->nullable(),
                        ]),
                    ]),

                Forms\Components\Section::make('Удобства и услуги')
                    ->description('Список доступных услуг')
                    ->icon('heroicon-m-wrench')
                    ->schema([
                        Forms\Components\TagsInput::make('amenities')
                            ->label('Удобства')
                            ->placeholder('WiFi, Парковка, Спа, Бассейн и т.д.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Политика и правила')
                    ->description('Важная информация для гостей')
                    ->icon('heroicon-m-document-text')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('check_in_time')
                                ->label('Время заезда')
                                ->options([
                                    '12:00' => '12:00',
                                    '14:00' => '14:00',
                                    '15:00' => '15:00',
                                    '16:00' => '16:00',
                                ])
                                ->default('15:00')
                                ->required(),

                            Forms\Components\Select::make('check_out_time')
                                ->label('Время выезда')
                                ->options([
                                    '10:00' => '10:00',
                                    '11:00' => '11:00',
                                    '12:00' => '12:00',
                                ])
                                ->default('11:00')
                                ->required(),

                            Forms\Components\TextInput::make('cancellation_days')
                                ->label('Бесплатная отмена за (дн)')
                                ->numeric()
                                ->minValue(0)
                                ->default(3)
                                ->required(),

                            Forms\Components\Toggle::make('pets_allowed')
                                ->label('Допускаются животные')
                                ->default(false),
                        ]),

                        Forms\Components\RichEditor::make('policies')
                            ->label('Политика гостиницы')
                            ->columnSpanFull()
                            ->placeholder('Внутренний распорядок, правила, ограничения'),
                    ]),

                Forms\Components\Section::make('Финансовые параметры')
                    ->description('Комиссия и платежи')
                    ->icon('heroicon-m-banknotes')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('commission_type')
                                ->label('Тип комиссии')
                                ->options([
                                    'percent' => 'Процент',
                                    'fixed' => 'Фиксированная сумма',
                                ])
                                ->default('percent')
                                ->required(),

                            Forms\Components\TextInput::make('commission_value')
                                ->label('Размер комиссии')
                                ->numeric()
                                ->step(0.01)
                                ->default(14)
                                ->required(),

                            Forms\Components\Select::make('payout_schedule')
                                ->label('График выплат')
                                ->options([
                                    'daily' => 'Ежедневно',
                                    'weekly' => 'Еженедельно',
                                    'biweekly' => 'Раз в две недели',
                                    'monthly' => 'Ежемесячно',
                                ])
                                ->default('weekly')
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Управление')
                    ->description('Статус и параметры управления')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Гостиница активна')
                                ->default(true)
                                ->required(),

                            Forms\Components\Toggle::make('is_verified')
                                ->label('Гостиница проверена')
                                ->default(false),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('Рекомендуемая гостиница')
                                ->default(false),
                        ]),
                    ]),

                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => filament()->getTenant()?->id),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => (string) Str::uuid()),
                Forms\Components\Hidden::make('business_group_id')
                    ->default(fn () => filament()->getTenant()?->active_business_group_id),
            ]);
        }

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office')
                    ->limit(50),

                Tables\Columns\TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stars')
                    ->label('Звёзды')
                    ->formatStateUsing(fn ($state) => '⭐' . str_repeat('⭐', $state - 1))
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('hotel_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'hostel' => 'Хостел',
                        'boutique' => 'Бутик-отель',
                        'resort' => 'Курортный отель',
                        'apartment' => 'Апартаменты',
                        'guest_house' => 'Гостевой дом',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'hostel' => 'green',
                        'boutique' => 'purple',
                        'resort' => 'orange',
                        'apartment' => 'gray',
                        'guest_house' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_rooms')
                    ->label('Номеров')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('available_rooms')
                    ->label('Свободно')
                    ->numeric()
                    ->sortable()
                    ->alignment('center')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 4.5 => 'success',
                        $state >= 4 => 'info',
                        $state >= 3.5 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_price')
                    ->label('Цена от')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->copyable()
                    ->limit(20),

                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Проверена')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Изменена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city')
                    ->label('Город')
                    ->relationship('city', 'name')
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('stars')
                    ->label('Звёзды')
                    ->options([
                        1 => '⭐ 1 звезда',
                        2 => '⭐⭐ 2 звезды',
                        3 => '⭐⭐⭐ 3 звезды',
                        4 => '⭐⭐⭐⭐ 4 звезды',
                        5 => '⭐⭐⭐⭐⭐ 5 звёзд',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('hotel_type')
                    ->label('Тип гостиницы')
                    ->options([
                        'hotel' => 'Гостиница',
                        'hostel' => 'Хостел',
                        'boutique' => 'Бутик-отель',
                        'resort' => 'Курортный отель',
                        'apartment' => 'Апартаменты',
                        'guest_house' => 'Гостевой дом',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Проверена'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Рекомендуемая'),

                Tables\Filters\Filter::make('available_rooms')
                    ->label('Есть свободные номера')
                    ->query(fn (Builder $query) => $query->whereRaw('available_rooms > 0')),

                Tables\Filters\Filter::make('high_rating')
                    ->label('Высокий рейтинг (≥4.0)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

                Tables\Filters\TrashedFilter::make()
                    ->label('Удалённые'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\Action::make('verify')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->label('Подтвердить')
                        ->visible(fn ($record) => !$record->is_verified)
                        ->action(function ($record) {
                            $record->update(['is_verified' => true]);
                            app(LoggerInterface::class)->info('Hotel verified', [
                                'hotel_id' => $record->id,
                                'user_id' => filament()->auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Tables\Actions\Action::make('feature')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->label('В рекомендуемые')
                        ->visible(fn ($record) => !$record->is_featured)
                        ->action(function ($record) {
                            $record->update(['is_featured' => true]);
                            app(LoggerInterface::class)->info('Hotel featured', [
                                'hotel_id' => $record->id,
                                'user_id' => filament()->auth()->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                app(LoggerInterface::class)->info('Hotel bulk deleted', [
                                    'hotel_id' => $record->id,
                                    'user_id' => filament()->auth()->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        }),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                                app(LoggerInterface::class)->info('Hotel activated', [
                                    'hotel_id' => $record->id,
                                    'user_id' => filament()->auth()->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                                app(LoggerInterface::class)->info('Hotel deactivated', [
                                    'hotel_id' => $record->id,
                                    'user_id' => filament()->auth()->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    Tables\Actions\BulkAction::make('verify')
                        ->label('Подтвердить')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_verified' => true]);
                                app(LoggerInterface::class)->info('Hotel bulk verified', [
                                    'hotel_id' => $record->id,
                                    'user_id' => filament()->auth()->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\ListHotels::route('/'),
                'create' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\CreateHotel::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\ViewHotel::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Hotels\HotelResource\Pages\EditHotel::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()?->id);
        }
}

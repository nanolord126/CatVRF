<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RestaurantResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Restaurant::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

        protected static ?string $navigationGroup = 'Food';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Название, контакты и описание ресторана')
                    ->icon('heroicon-m-building-storefront')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => (string) Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Название ресторана')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Итальянский ресторан "Bella Italia"'),

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
                                ->placeholder('info@restaurant.com')
                                ->copyable(),

                            Forms\Components\TextInput::make('website')
                                ->label('Веб-сайт')
                                ->url()
                                ->nullable()
                                ->placeholder('https://restaurant.com'),
                        ]),

                        Forms\Components\RichEditor::make('description')
                            ->label('Описание ресторана')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder('Подробное описание, специалитеты, атмосфера'),

                        Forms\Components\FileUpload::make('main_photo')
                            ->label('Главное фото')
                            ->image()
                            ->directory('restaurants/photos')
                            ->visibility('public')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('gallery_photos')
                            ->label('Галерея (несколько фото)')
                            ->image()
                            ->directory('restaurants/gallery')
                            ->multiple()
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Месторасположение')
                    ->description('Адрес и географические координаты')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->placeholder('Улица, дом, город'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('city')
                                ->label('Город')
                                ->required()
                                ->placeholder('Москва'),

                            Forms\Components\TextInput::make('postal_code')
                                ->label('Почтовый индекс')
                                ->placeholder('123456'),

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

                Forms\Components\Section::make('Кухня и категория')
                    ->description('Тип кухни и ресторана')
                    ->icon('heroicon-m-fire')
                    ->schema([
                        Forms\Components\Select::make('cuisine_type')
                            ->label('Кухня (несколько вариантов)')
                            ->multiple()
                            ->options([
                                'italian' => '🇮🇹 Итальянская',
                                'japanese' => '🇯🇵 Японская',
                                'russian' => '🇷🇺 Русская',
                                'asian' => '🌏 Азиатская',
                                'fastfood' => '⚡ Фастфуд',
                                'mexican' => '🌶️ Мексиканская',
                                'indian' => '🍛 Индийская',
                                'chinese' => '🥢 Китайская',
                                'seafood' => '🦞 Морепродукты',
                                'vegetarian' => '🥗 Вегетарианская',
                                'vegan' => '🌱 Веган',
                                'french' => '🇫🇷 Французская',
                            ])
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('restaurant_type')
                            ->label('Тип заведения')
                            ->options([
                                'restaurant' => 'Ресторан',
                                'cafe' => 'Кафе',
                                'bistro' => 'Бистро',
                                'pizzeria' => 'Пиццерия',
                                'coffeeshop' => 'Кофейня',
                                'fastfood' => 'Фастфуд',
                                'bar' => 'Бар/Паб',
                                'food_court' => 'Фуд-корт',
                            ])
                            ->default('restaurant')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Расписание и доставка')
                    ->description('Время работы, доставка, условия')
                    ->icon('heroicon-m-clock')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('open_time')
                                ->label('Время открытия')
                                ->placeholder('09:00')
                                ->required(),

                            Forms\Components\TextInput::make('close_time')
                                ->label('Время закрытия')
                                ->placeholder('23:00')
                                ->required(),

                            Forms\Components\Toggle::make('is_delivery_available')
                                ->label('Доставка доступна')
                                ->default(true),

                            Forms\Components\TextInput::make('delivery_time_min')
                                ->label('Время доставки минимум (мин)')
                                ->numeric()
                                ->default(30)
                                ->minValue(0),

                            Forms\Components\TextInput::make('delivery_radius_km')
                                ->label('Радиус доставки (км)')
                                ->numeric()
                                ->default(5)
                                ->minValue(0),

                            Forms\Components\TextInput::make('min_order_amount')
                                ->label('Минимум заказа (₽)')
                                ->numeric()
                                ->default(500)
                                ->minValue(0),
                        ]),
                    ]),

                Forms\Components\Section::make('Рейтинг и характеристики')
                    ->description('Оценки и особенности')
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

                            Forms\Components\TextInput::make('avg_check')
                                ->label('Средний чек (₽)')
                                ->numeric()
                                ->step(100)
                                ->minValue(0),
                        ]),

                        Forms\Components\TagsInput::make('features')
                            ->label('Особенности')
                            ->placeholder('Веранда, WiFi, Паркинг, Бизнес-ланч')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Посадочные места')
                    ->description('Количество столиков и вместимость')
                    ->icon('heroicon-m-users')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('total_tables')
                                ->label('Всего столиков')
                                ->numeric()
                                ->minValue(1)
                                ->required(),

                            Forms\Components\TextInput::make('total_seats')
                                ->label('Всего мест')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Финансовые параметры')
                    ->description('Комиссия платформы и выплаты')
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
                                ->step(0.1)
                                ->default(14)
                                ->required()
                                ->minValue(0),

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

                Forms\Components\Section::make('Управление и статус')
                    ->description('Активность, проверка, рекомендации')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Ресторан активен')
                                ->default(true)
                                ->required(),

                            Forms\Components\Toggle::make('is_verified')
                                ->label('Проверен администрацией')
                                ->default(false),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('В рекомендуемых')
                                ->default(false),

                            Forms\Components\Toggle::make('is_premium')
                                ->label('Premium ресторан')
                                ->default(false),
                        ]),
                    ]),

                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => tenant('id')),
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
                    ->icon('heroicon-m-building-storefront')
                    ->limit(50),

                Tables\Columns\TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('restaurant_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'restaurant' => 'Ресторан',
                        'cafe' => 'Кафе',
                        'bistro' => 'Бистро',
                        'pizzeria' => 'Пиццерия',
                        'coffeeshop' => 'Кофейня',
                        'fastfood' => 'Фастфуд',
                        'bar' => 'Бар',
                        'food_court' => 'Фуд-корт',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'restaurant' => 'blue',
                        'cafe' => 'green',
                        'bistro' => 'purple',
                        'pizzeria' => 'orange',
                        'coffeeshop' => 'amber',
                        'fastfood' => 'red',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_seats')
                    ->label('Мест')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('avg_check')
                    ->label('Средний чек')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('review_count')
                    ->label('Отзывы')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\IconColumn::make('is_delivery_available')
                    ->label('Доставка')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_verified')
                    ->label('✓ Проверен')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Активен')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\BooleanColumn::make('is_featured')
                    ->label('⭐ Рекомендуемый')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('restaurant_type')
                    ->label('Тип заведения')
                    ->options([
                        'restaurant' => 'Ресторан',
                        'cafe' => 'Кафе',
                        'bistro' => 'Бистро',
                        'pizzeria' => 'Пиццерия',
                        'coffeeshop' => 'Кофейня',
                        'fastfood' => 'Фастфуд',
                        'bar' => 'Бар',
                        'food_court' => 'Фуд-корт',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('city')
                    ->label('Город')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Проверен'),

                Tables\Filters\TernaryFilter::make('is_delivery_available')
                    ->label('Доставка доступна'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Рекомендуемый'),

                Tables\Filters\Filter::make('high_rating')
                    ->label('Высокий рейтинг (≥4.0)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

                Tables\Filters\Filter::make('budget_friendly')
                    ->label('Бюджетные (чек <1000 ₽)')
                    ->query(fn (Builder $query) => $query->where('avg_check', '<', 100000)),

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
                            Log::channel('audit')->info('Restaurant verified', [
                                'restaurant_id' => $record->id,
                                'user_id' => auth()->id(),
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
                            Log::channel('audit')->info('Restaurant featured', [
                                'restaurant_id' => $record->id,
                                'user_id' => auth()->id(),
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
                                Log::channel('audit')->info('Restaurant bulk deleted', [
                                    'restaurant_id' => $record->id,
                                    'user_id' => auth()->id(),
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
                                Log::channel('audit')->info('Restaurant activated', [
                                    'restaurant_id' => $record->id,
                                    'user_id' => auth()->id(),
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
                                Log::channel('audit')->info('Restaurant deactivated', [
                                    'restaurant_id' => $record->id,
                                    'user_id' => auth()->id(),
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
                                Log::channel('audit')->info('Restaurant bulk verified', [
                                    'restaurant_id' => $record->id,
                                    'user_id' => auth()->id(),
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
                'index' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\ListRestaurants::route('/'),
                'create' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\CreateRestaurant::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\ViewRestaurant::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Food\RestaurantResource\Pages\EditRestaurant::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id);
        }
}

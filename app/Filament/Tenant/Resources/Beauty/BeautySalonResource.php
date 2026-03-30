<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautySalonResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = BeautySalon::class;
        protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
        protected static ?string $navigationLabel = 'Салоны Красоты';
        protected static ?string $navigationGroup = 'Beauty & Wellness';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Основная информация')
                        ->description('Базовые данные салона красоты')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->default(fn () => (string) Str::uuid())
                                ->disabled()
                                ->dehydrated()
                                ->required(),
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
                        ])->columns(2),

                    Forms\Components\Section::make('Контактная информация')
                        ->description('Способы связи с клиентами')
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
                        ])->columns(3),

                    Forms\Components\Section::make('Геолокация')
                        ->description('GPS координаты для карты')
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
                        ])->columns(2),

                    Forms\Components\Section::make('Статус и расписание')
                        ->description('Состояние и время работы')
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
                        ])->columns(2),

                    Forms\Components\Section::make('Комиссии и платежи')
                        ->description('Настройки платформенной комиссии')
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
                        ])->columns(3),

                    Forms\Components\Section::make('Метаданные')
                        ->description('Системная информация')
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
                        ])->columns(2),

                    Forms\Components\Hidden::make('tenant_id')
                        ->default(fn () => tenant('id')),
                    Forms\Components\Hidden::make('correlation_id')
                        ->default(fn () => (string) Str::uuid()),
                    Forms\Components\Hidden::make('business_group_id')
                        ->default(fn () => filament()->getTenant()?->active_business_group_id),
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
                    Tables\Columns\BadgeColumn::make('rating')
                        ->label('Рейтинг')
                        ->numeric(1)
                        ->color(fn ($state) => match (true) {
                            $state >= 4.5 => 'success',
                            $state >= 3.5 => 'info',
                            default => 'warning',
                        }),
                    Tables\Columns\TextColumn::make('review_count')
                        ->label('Отзывы')
                        ->sortable(),
                    Tables\Columns\BooleanColumn::make('is_verified')
                        ->label('Верифицирован')
                        ->trueIcon('heroicon-o-check-circle')
                        ->falseIcon('heroicon-o-x-circle'),
                    Tables\Columns\BooleanColumn::make('is_active')
                        ->label('Активен')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('commission_value')
                        ->label('Комиссия')
                        ->formatStateUsing(fn ($state, $record) => "{$state}%"),
                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Дата создания')
                        ->date()
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
                    Tables\Actions\ViewAction::make()
                        ->before(function ($record) {
                            Log::channel('audit')->info('Filament Resource View: BeautySalon', [
                                'id' => $record->id,
                                'tenant_id' => tenant('id'),
                            ]);
                        }),
                    Tables\Actions\EditAction::make()
                        ->before(function ($record) {
                            Log::channel('audit')->info('Filament Resource Edit: BeautySalon', [
                                'id' => $record->id,
                                'tenant_id' => tenant('id'),
                            ]);
                        }),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                        Tables\Actions\BulkAction::make('verify')
                            ->label('Верифицировать')
                            ->icon('heroicon-o-check-circle')
                            ->action(function ($records) {
                                foreach ($records as $record) {
                                    $record->update(['is_verified' => true]);
                                    Log::channel('audit')->info('Beauty Salon bulk verified', [
                                        'id' => $record->id,
                                        'tenant_id' => tenant('id'),
                                    ]);
                                }
                            }),
                    ]),
                ])
                ->paginated([10, 25, 50, 100]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'));
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListBeautySalons::route('/'),
                'create' => Pages\CreateBeautySalon::route('/create'),
                'view' => Pages\ViewBeautySalon::route('/{record}'),
                'edit' => Pages\EditBeautySalon::route('/{record}/edit'),
            ];
        }
}

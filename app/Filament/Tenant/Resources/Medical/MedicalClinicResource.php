<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
    use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{Action, EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class MedicalClinicResource extends Resource
    {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

        protected static ?string $model = Clinic::class;
        protected static ?string $navigationIcon = 'heroicon-m-hospital-box';
        protected static ?string $navigationGroup = 'Medical';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-building-office-2')
                    ->description('Данные клиники')
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->default(fn () => Str::uuid())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->copyable()
                            ->columnSpan(1),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->copyable()
                            ->columnSpan(1),

                        RichEditor::make('description')
                            ->label('Описание')
                            ->columnSpan('full'),

                        FileUpload::make('logo')
                            ->label('Логотип')
                            ->image()
                            ->directory('clinics')
                            ->columnSpan(1),

                        FileUpload::make('gallery')
                            ->label('Фотогалерея')
                            ->image()
                            ->multiple()
                            ->directory('clinics')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Местоположение')
                    ->icon('heroicon-m-map-pin')
                    ->schema([
                        TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('city')
                            ->label('Город')
                            ->required()
                            ->columnSpan(1),

                        TextInput::make('latitude')
                            ->label('Широта')
                            ->numeric()
                            ->step(0.0001)
                            ->columnSpan(1),

                        TextInput::make('longitude')
                            ->label('Долгота')
                            ->numeric()
                            ->step(0.0001)
                            ->columnSpan(1),

                        TextInput::make('postal_code')
                            ->label('Индекс')
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Специализация')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('clinic_type')
                            ->label('Тип клиники')
                            ->options([
                                'general' => 'Общая практика',
                                'surgery' => 'Хирургическая',
                                'pediatric' => 'Педиатрическая',
                                'obstetric' => 'Акушерская',
                                'dental' => 'Стоматология',
                                'cardio' => 'Кардиология',
                                'oncology' => 'Онкология',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TagsInput::make('specializations')
                            ->label('Специализации')
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Услуги')
                    ->icon('heroicon-m-heart')
                    ->schema([
                        Toggle::make('has_emergency')
                            ->label('Экстренная помощь')
                            ->columnSpan(1),

                        Toggle::make('has_surgery')
                            ->label('Операционная')
                            ->columnSpan(1),

                        Toggle::make('has_ultrasound')
                            ->label('УЗ диагностика')
                            ->columnSpan(1),

                        Toggle::make('has_laboratory')
                            ->label('Лаборатория')
                            ->columnSpan(1),

                        Toggle::make('telehealth_available')
                            ->label('Онлайн консультации')
                            ->columnSpan(1),

                        Toggle::make('accepts_insurance')
                            ->label('Приём страховки')
                            ->columnSpan(1),

                        TagsInput::make('services_list')
                            ->label('Услуги')
                            ->columnSpan(2),
                    ])->columns(4),

                Section::make('Контакты и расписание')
                    ->icon('heroicon-m-clock')
                    ->schema([
                        TextInput::make('website')
                            ->label('Сайт')
                            ->url()
                            ->columnSpan(2),

                        TextInput::make('working_hours_from')
                            ->label('Часы с')
                            ->columnSpan(1),

                        TextInput::make('working_hours_to')
                            ->label('Часы до')
                            ->columnSpan(1),

                        RichEditor::make('appointment_rules')
                            ->label('Правила записи')
                            ->columnSpan('full'),
                    ])->columns(4),

                Section::make('Рейтинг и статус')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        TextInput::make('review_count')
                            ->label('Отзывов')
                            ->numeric()
                            ->disabled()
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->columnSpan(1),

                        Toggle::make('is_verified')
                            ->label('✓ Проверена')
                            ->columnSpan(1),

                        Toggle::make('is_featured')
                            ->label('⭐ Рекомендуемая')
                            ->columnSpan(1),

                        Toggle::make('is_premium')
                            ->label('💎 Премиум')
                            ->columnSpan(1),
                    ])->columns(4),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')
                            ->default(fn () => tenant('id')),

                        Hidden::make('correlation_id')
                            ->default(fn () => Str::uuid()),

                        Hidden::make('business_group_id')
                            ->default(fn () => filament()->getTenant()?->active_business_group_id),

                        TextInput::make('created_at')
                            ->label('Создана')
                            ->disabled()
                            ->columnSpan(2),

                        TextInput::make('updated_at')
                            ->label('Обновлена')
                            ->disabled()
                            ->columnSpan(2),
                    ])->columns(4),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office-2')
                    ->limit(50),

                TextColumn::make('city')
                    ->label('Город')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('clinic_type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'surgery' => 'red',
                        'pediatric' => 'green',
                        'obstetric' => 'pink',
                        'dental' => 'yellow',
                        'cardio' => 'purple',
                        'oncology' => 'orange',
                        default => 'gray',
                    }),

                TextColumn::make('rating')
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

                TextColumn::make('review_count')
                    ->label('Отзывы')
                    ->numeric()
                    ->alignment('center'),

                BadgeColumn::make('specializations')
                    ->label('Специализации')
                    ->formatStateUsing(fn ($state) => count((array)$state) . ' шт')
                    ->color('info'),

                BooleanColumn::make('has_emergency')
                    ->label('Скорая')
                    ->toggleable(),

                BooleanColumn::make('telehealth_available')
                    ->label('Онлайн')
                    ->toggleable(),

                BooleanColumn::make('is_verified')
                    ->label('✓ Проверена')
                    ->toggleable()
                    ->sortable(),

                BooleanColumn::make('is_featured')
                    ->label('⭐ Рекомендуемая')
                    ->toggleable(),

                BooleanColumn::make('is_active')
                    ->label('Активна')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('clinic_type')
                    ->label('Тип')
                    ->options([
                        'general' => 'Общая практика',
                        'surgery' => 'Хирургическая',
                        'pediatric' => 'Педиатрическая',
                        'obstetric' => 'Акушерская',
                        'dental' => 'Стоматология',
                        'cardio' => 'Кардиология',
                        'oncology' => 'Онкология',
                    ])
                    ->multiple(),

                SelectFilter::make('city')
                    ->label('Город')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('is_verified')
                    ->label('Проверена'),

                TernaryFilter::make('is_featured')
                    ->label('Рекомендуемая'),

                TernaryFilter::make('has_emergency')
                    ->label('Скорая помощь'),

                TernaryFilter::make('telehealth_available')
                    ->label('Онлайн консультации'),

                Filter::make('high_rating')
                    ->label('Высокий рейтинг (≥4.0)')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.0)),

                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),

                    Action::make('verify')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->label('Подтвердить')
                        ->visible(fn ($record) => !$record->is_verified)
                        ->action(function ($record) {
                            $record->update(['is_verified' => true]);
                            $this->logger->info('Clinic verified', [
                                'clinic_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),

                    Action::make('feature')
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->label('Рекомендовать')
                        ->visible(fn ($record) => !$record->is_featured)
                        ->action(function ($record) {
                            $record->update(['is_featured' => true]);
                            $this->logger->info('Clinic featured', [
                                'clinic_id' => $record->id,
                                'user_id' => $this->guard->id(),
                                'correlation_id' => $record->correlation_id,
                            ]);
                        })
                        ->successNotification(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                                $this->logger->info('Clinic bulk activated', [
                                    'clinic_id' => $record->id,
                                    'user_id' => $this->guard->id(),
                                    'correlation_id' => $record->correlation_id,
                                ]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotification(),

                    BulkAction::make('verify')
                        ->label('Подтвердить')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_verified' => true]);
                                $this->logger->info('Clinic bulk verified', [
                                    'clinic_id' => $record->id,
                                    'user_id' => $this->guard->id(),
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

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Medical\Pages\ListClinics::route('/'),
                'create' => \App\Filament\Tenant\Resources\Medical\Pages\CreateClinic::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Medical\Pages\ViewClinic::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Medical\Pages\EditClinic::route('/{record}/edit'),
            ];
        }

        public static function getRelations(): array
        {
            return [];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant('id'));
        }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Auto;

use Filament\Resources\Resource;

final class AutoResource extends Resource
{

    protected static ?string $model = TaxiDriver::class;
        protected static ?string $navigationIcon = 'heroicon-o-vehicle-seat';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация водителя')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')->label('ФИО')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('license_number')->label('Номер лицензии')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('driver_id')->label('Водительское удостоверение')->columnSpan(1),
                        Select::make('status')->label('Статус')->options([
                            'active' => 'Активен',
                            'inactive' => 'Неактивен',
                            'on_break' => 'На перерыве',
                            'suspended' => 'Приостановлен'
                        ])->required()->columnSpan(1),
                        DatePicker::make('license_expiration')->label('Лицензия до')->columnSpan(1),
                    ]),

                Section::make('Контактная информация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->columnSpan(1),
                        TextInput::make('city')->label('Город')->maxLength(100)->columnSpan(1),
                        TextInput::make('current_location')->label('Текущее местоположение')->columnSpan(1),
                    ]),

                Section::make('Автомобиль')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('vehicle_brand')->label('Марка')->maxLength(100)->columnSpan(1),
                        TextInput::make('vehicle_model')->label('Модель')->maxLength(100)->columnSpan(1),
                        TextInput::make('license_plate')->label('Номерной знак')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('vin_number')->label('VIN')->columnSpan(1),
                        TextInput::make('year')->label('Год выпуска')->numeric()->columnSpan(1),
                        Select::make('class')->label('Класс такси')->options([
                            'economy' => 'Эконом',
                            'comfort' => 'Комфорт',
                            'business' => 'Бизнес',
                            'premium' => 'Премиум'
                        ])->columnSpan(1),
                        TextInput::make('seats_count')->label('Мест в салоне')->numeric()->columnSpan(1),
                        TextInput::make('trunk_volume')->label('Объём багажника (л)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Документы и страховка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('license_scan')->label('Лицензия (скан)')->acceptedFileTypes(['application/pdf'])->directory('auto-licenses'),
                        FileUpload::make('vehicle_registration')->label('Свидетельство о регистрации')->acceptedFileTypes(['application/pdf'])->directory('auto-registration'),
                        FileUpload::make('insurance_policy')->label('Полис ОСАГО')->acceptedFileTypes(['application/pdf'])->directory('auto-insurance'),
                        DatePicker::make('insurance_expiration')->label('ОСАГО до')->columnSpan(1),
                        Toggle::make('has_dtp_history')->label('История ДТП')->columnSpan(1),
                    ]),

                Section::make('Рейтинг и производительность')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                        TextInput::make('total_trips')->label('Всего поездок')->numeric()->columnSpan(1),
                        TextInput::make('completed_trips')->label('Завершённых')->numeric()->columnSpan(1),
                        TextInput::make('cancelled_trips')->label('Отменено')->numeric()->columnSpan(1),
                        TextInput::make('avg_trip_rating')->label('Средний рейтинг поездки')->numeric(decimals: 1)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                    ]),

                Section::make('Стоимость услуг')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('commission_percent')->label('Комиссия платформы (%)')->numeric()->columnSpan(1),
                        TextInput::make('surge_multiplier')->label('Множитель Surge')->numeric()->step(0.1)->columnSpan(1),
                        Toggle::make('accepts_cash')->label('Принимает наличные')->columnSpan(1),
                        Toggle::make('accepts_card')->label('Принимает карты')->columnSpan(1),
                        Toggle::make('accepts_apple_pay')->label('Apple Pay')->columnSpan(1),
                        Toggle::make('accepts_google_pay')->label('Google Pay')->columnSpan(1),
                    ]),

                Section::make('Специальные услуги')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_wifi')->label('WiFi в автомобиле')->columnSpan(1),
                        Toggle::make('has_charging_port')->label('Зарядка для телефона')->columnSpan(1),
                        Toggle::make('has_water')->label('Бутилированная вода')->columnSpan(1),
                        Toggle::make('has_mints')->label('Конфеты')->columnSpan(1),
                        Toggle::make('speaks_english')->label('Говорит по-английски')->columnSpan(1),
                        Toggle::make('pet_friendly')->label('Перевоз животных')->columnSpan(1),
                        Toggle::make('wheelchair_accessible')->label('Доступен для инвалидов')->columnSpan(1),
                        Toggle::make('baby_seat_available')->label('Детское кресло')->columnSpan(1),
                    ]),

                Section::make('Квалификация и обучение')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        DatePicker::make('hire_date')->label('Дата начала работы')->columnSpan(1),
                        TextInput::make('years_experience')->label('Опыт вождения (лет)')->numeric()->columnSpan(1),
                        Toggle::make('background_check_passed')->label('Проверка пройдена')->columnSpan(1),
                        Toggle::make('safety_training_completed')->label('Обучение безопасности')->columnSpan(1),
                        TextInput::make('emergency_contact')->label('Контакт для чрезвычайных ситуаций')->columnSpan(1),
                        TextInput::make('emergency_phone')->label('Номер аварийного контакта')->tel()->columnSpan(1),
                    ]),

                Section::make('Работа и график')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours_start')->label('Начало работы')->columnSpan(1),
                        TextInput::make('working_hours_end')->label('Конец работы')->columnSpan(1),
                        Toggle::make('works_weekends')->label('Работает в выходные')->columnSpan(1),
                        Toggle::make('works_nights')->label('Ночные смены')->columnSpan(1),
                        TextInput::make('avg_earnings_daily')->label('Средний доход в день (₽)')->numeric()->columnSpan(1),
                        TextInput::make('avg_earnings_monthly')->label('Средний доход в месяц (₽)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('profile_photo')->label('Фото профиля')->image()->directory('auto-drivers'),
                        FileUpload::make('vehicle_photo')->label('Фото автомобиля')->image()->directory('auto-vehicles'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('auto-gallery')->columnSpan('full'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                        Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                        TagsInput::make('meta_keywords')->label('Meta Keywords')->columnSpan(2),
                    ]),

                Section::make('Управление')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Активно')->default(true),
                        Toggle::make('is_featured')->label('Избранное')->default(false),
                        Toggle::make('verified')->label('Проверено')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                ImageColumn::make('profile_photo')->label('Фото')->size(40),
                TextColumn::make('full_name')->label('ФИО')->searchable()->sortable()->weight('bold')->limit(30),
                TextColumn::make('vehicle_model')->label('Автомобиль')->searchable()->limit(20),
                TextColumn::make('class')->label('Класс')->badge()->color('info'),
                TextColumn::make('rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning')->sortable(),
                TextColumn::make('total_trips')->label('Поездок')->numeric(),
                BadgeColumn::make('is_active')->label('Активен')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('speaks_english')->label('English')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('pet_friendly')->label('Животные')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('wheelchair_accessible')->label('Инвалиды')->colors(['success' => true, 'gray' => false]),
                TextColumn::make('license_number')->label('Лицензия')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('class')->options([
                    'economy' => 'Эконом',
                    'comfort' => 'Комфорт',
                    'business' => 'Бизнес',
                    'premium' => 'Премиум'
                ]),
                Filter::make('pet_friendly')->query(fn (Builder $q) => $q->where('pet_friendly', true))->label('Животные разрешены'),
                Filter::make('wheelchair')->query(fn (Builder $q) => $q->where('wheelchair_accessible', true))->label('Для инвалидов'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('rating', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListAuto::route('/'),
                'create' => Pages\CreateAuto::route('/create'),
                'edit' => Pages\EditAuto::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

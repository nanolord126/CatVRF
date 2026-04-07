<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HomeServices;

use Filament\Resources\Resource;

final class HomeServicesResource extends Resource
{

    protected static ?string $model = Contractor::class;
        protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')->label('ФИО')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('contractor_code')->label('Код мастера')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('service_type')->label('Тип услуги')->options([
                            'plumbing' => 'Сантехника',
                            'electrical' => 'Электричество',
                            'carpentry' => 'Столярные работы',
                            'painting' => 'Покраска',
                            'cleaning' => 'Уборка',
                            'repair' => 'Ремонт',
                            'appliance_repair' => 'Ремонт техники',
                            'hvac' => 'Кондиционирование'
                        ])->required()->columnSpan(1),
                        TextInput::make('company_name')->label('Компания')->maxLength(255)->columnSpan(1),
                    ]),

                Section::make('Контактная информация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->columnSpan(1),
                        TextInput::make('city')->label('Город')->maxLength(100)->required()->columnSpan(1),
                        TextInput::make('address')->label('Адрес')->maxLength(500)->columnSpan(1),
                        TextInput::make('website')->label('Сайт')->url()->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('О мастере')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_bio')->label('Краткая информация')->maxLength(500)->rows(3),
                        RichEditor::make('full_bio')->label('Полная информация')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Опыт и квалификация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('years_experience')->label('Опыт (лет)')->numeric()->columnSpan(1),
                        TextInput::make('completed_jobs')->label('Завершённых работ')->numeric()->columnSpan(1),
                        DatePicker::make('start_year')->label('Год начала работы')->columnSpan(1),
                        TagsInput::make('certifications')->label('Сертификаты')->columnSpan(2),
                        TagsInput::make('licenses')->label('Лицензии')->columnSpan(2),
                        Textarea::make('specialties')->label('Специализация')->maxLength(1000)->rows(3)->columnSpan(2),
                    ]),

                Section::make('Услуги и ценообразование')
                    ->collapsed()
                    ->schema([
                        Repeater::make('services')->label('Предоставляемые услуги')
                            ->schema([
                                TextInput::make('service_name')->label('Название услуги')->required(),
                                TextInput::make('price')->label('Цена (₽)')->numeric()->required(),
                                TextInput::make('unit')->label('Единица (м², час)')->required(),
                                Textarea::make('description')->label('Описание')->maxLength(500)->rows(2),
                            ])->columnSpan('full'),
                    ]),

                Section::make('Цены и условия')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('hourly_rate')->label('Часовая ставка (₽)')->numeric()->columnSpan(1),
                        TextInput::make('min_job_price')->label('Минимальная сумма работ (₽)')->numeric()->columnSpan(1),
                        TextInput::make('call_out_fee')->label('Выездное (₽)')->numeric()->columnSpan(1),
                        TextInput::make('deposit_percent')->label('Предоплата (%)')->numeric()->columnSpan(1),
                        Toggle::make('accepts_cash')->label('Наличные')->columnSpan(1),
                        Toggle::make('accepts_card')->label('Карты')->columnSpan(1),
                        Toggle::make('accepts_bank_transfer')->label('Банковский перевод')->columnSpan(1),
                        Toggle::make('offers_credit')->label('Услуга в кредит')->columnSpan(1),
                    ]),

                Section::make('График работы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours_start')->label('Начало (время)')->columnSpan(1),
                        TextInput::make('working_hours_end')->label('Конец (время)')->columnSpan(1),
                        Toggle::make('works_weekends')->label('Выходные')->columnSpan(1),
                        Toggle::make('works_evenings')->label('Вечера')->columnSpan(1),
                        Toggle::make('emergency_service')->label('Аварийная служба 24/7')->columnSpan(1),
                        TextInput::make('response_time_hours')->label('Время ответа (часов)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Оборудование и материалы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('tools')->label('Инструменты')->columnSpan(2),
                        Toggle::make('has_transport')->label('Собственный транспорт')->columnSpan(1),
                        Toggle::make('provides_materials')->label('Предоставляет материалы')->columnSpan(1),
                        Toggle::make('allows_client_materials')->label('Работает с материалами клиента')->columnSpan(1),
                        TextInput::make('material_markup_percent')->label('Наценка на материалы (%)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Гарантия и страховка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('warranty_days')->label('Гарантия (дней)')->numeric()->columnSpan(1),
                        Textarea::make('warranty_terms')->label('Условия гарантии')->maxLength(500)->rows(2)->columnSpan(2),
                        Toggle::make('has_liability_insurance')->label('Страховка ответственности')->columnSpan(1),
                        Toggle::make('has_work_guarantee')->label('Гарантия качества')->columnSpan(1),
                    ]),

                Section::make('Рейтинг и отзывы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                        TextInput::make('repeat_client_percent')->label('Постоянные клиенты (%)')->numeric()->columnSpan(1),
                        TextInput::make('avg_job_rating')->label('Средний рейтинг работы')->numeric(decimals: 1)->columnSpan(1),
                    ]),

                Section::make('Обслуживаемые районы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('service_radius_km')->label('Радиус обслуживания (км)')->numeric()->columnSpan(1),
                        TagsInput::make('service_areas')->label('Обслуживаемые районы')->columnSpan(2),
                        Toggle::make('offers_remote_consultation')->label('Удалённая консультация')->columnSpan(1),
                        Toggle::make('offers_online_booking')->label('Онлайн-бронирование')->columnSpan(1),
                    ]),

                Section::make('Портфолио')
                    ->collapsed()
                    ->schema([
                        TextInput::make('portfolio_url')->label('Портфолио')->url(),
                        FileUpload::make('portfolio_images')->label('Фото работ')->multiple()->image()->directory('home-services-portfolio')->columnSpan('full'),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('profile_photo')->label('Фото профиля')->image()->directory('home-services-profile'),
                        FileUpload::make('company_logo')->label('Логотип компании')->image()->directory('home-services-logo'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('home-services-gallery')->columnSpan('full'),
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
                TextColumn::make('service_type')->label('Услуга')->badge()->color('info'),
                TextColumn::make('city')->label('Город')->searchable(),
                TextColumn::make('rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning')->sortable(),
                TextColumn::make('completed_jobs')->label('Работ')->numeric(),
                TextColumn::make('hourly_rate')->label('Часовая (₽)')->numeric()->badge()->color('success'),
                BadgeColumn::make('has_liability_insurance')->label('Страховка')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('emergency_service')->label('24/7')->colors(['warning' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('phone')->label('Телефон')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('service_type')->options([
                    'plumbing' => 'Сантехника',
                    'electrical' => 'Электричество',
                    'carpentry' => 'Столярные работы',
                    'painting' => 'Покраска',
                ]),
                Filter::make('insurance')->query(fn (Builder $q) => $q->where('has_liability_insurance', true))->label('Со страховкой'),
                Filter::make('emergency')->query(fn (Builder $q) => $q->where('emergency_service', true))->label('Аварийные 24/7'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('rating', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListHomeServices::route('/'),
                'create' => Pages\CreateHomeServices::route('/create'),
                'edit' => Pages\EditHomeServices::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

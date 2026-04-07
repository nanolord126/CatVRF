<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\OfficeCatering;

use Filament\Resources\Resource;

final class OfficeCateringResource extends Resource
{

    protected static ?string $model = OfficeCatering::class;
        protected static ?string $navigationIcon = 'heroicon-o-cake';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название компании')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('company_id')->label('ID компании')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                        TextInput::make('contact_person')->label('Контактное лицо')->columnSpan(1),
                        Select::make('status')->label('Статус')->options(['active' => 'Активна', 'inactive' => 'Неактивна', 'trial' => 'Триал'])->default('active')->columnSpan(2),
                    ]),

                Section::make('Адрес доставки')
                    ->columns(2)
                    ->schema([
                        TextInput::make('office_address')->label('Адрес офиса')->required()->columnSpan(2),
                        TextInput::make('city')->label('Город')->required()->columnSpan(1),
                        TextInput::make('zip_code')->label('Почтовый код')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('Параметры подписки')
                    ->columns(2)
                    ->schema([
                        Select::make('subscription_type')->label('Тип подписки')->options([
                            'daily' => 'Ежедневная',
                            'weekly' => 'Еженедельная',
                            'monthly' => 'Ежемесячная',
                            'custom' => 'Кастомная'
                        ])->required()->columnSpan(1),
                        TextInput::make('employee_count')->label('Количество сотрудников')->numeric()->required()->columnSpan(1),
                        TextInput::make('daily_cost')->label('Стоимость в день (₽)')->numeric()->required()->columnSpan(1),
                        Select::make('meal_type')->label('Тип питания')->options([
                            'lunch' => 'Только обед',
                            'breakfast_lunch' => 'Завтрак + обед',
                            'all_day' => 'Полный день'
                        ])->required()->columnSpan(1),
                    ]),

                Section::make('Предпочтения в питании')
                    ->columns(2)
                    ->schema([
                        TagsInput::make('dietary_preferences')->label('Диеты')->columnSpan(2),
                        Toggle::make('vegetarian_option')->label('Вегетарианские блюда')->columnSpan(1),
                        Toggle::make('vegan_option')->label('Веган блюда')->columnSpan(1),
                        TagsInput::make('allergies')->label('Аллергены для исключения')->columnSpan(2),
                    ]),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        Textarea::make('special_requirements')->label('Особые требования')->maxLength(1000)->rows(3)->columnSpan('full'),
                    ]),

                Section::make('График доставки')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('delivery_time')->label('Время доставки')->placeholder('12:00')->columnSpan(1),
                        Select::make('delivery_days')->label('Дни доставки')->options([
                            'mon_fri' => 'Пн-Пт',
                            'all_week' => 'Ежедневно',
                            'custom' => 'Кастомный'
                        ])->columnSpan(1),
                        Toggle::make('delivery_weekends')->label('Доставка в выходные')->columnSpan(1),
                        TextInput::make('delivery_address_count')->label('Адресов доставки')->numeric()->columnSpan(1),
                    ]),

                Section::make('Тарифицирование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('commission_percent')->label('Комиссия платформы (%)')->numeric()->columnSpan(1),
                        TextInput::make('min_order_value')->label('Минимальная сумма заказа')->numeric()->columnSpan(1),
                        Toggle::make('prepay_required')->label('Требуется предоплата')->columnSpan(1),
                        TextInput::make('payment_terms_days')->label('Срок оплаты (дни)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Контакты поставщиков')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('supplier_list')->label('Список поставщиков (Excel)')->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->directory('catering-suppliers'),
                        TextInput::make('whatsapp')->label('WhatsApp')->tel()->columnSpan(1),
                        TextInput::make('telegram')->label('Telegram')->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')->label('Логотип')->image()->directory('catering-logos'),
                        FileUpload::make('gallery')->label('Галерея блюд')->multiple()->image()->directory('catering-gallery')->columnSpan('full'),
                        FileUpload::make('menu_pdf')->label('PDF меню')->acceptedFileTypes(['application/pdf'])->directory('catering-menus'),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('meta_title')->label('Meta Title')->maxLength(60),
                        Textarea::make('meta_description')->label('Meta Description')->maxLength(160)->rows(2)->columnSpan(2),
                        TagsInput::make('keywords')->label('Ключевые слова')->columnSpan(2),
                    ]),

                Section::make('Управление')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Активна')->default(true),
                        Toggle::make('is_featured')->label('Избранная')->default(false),
                        Toggle::make('verified')->label('Проверена')->default(false),
                        TextInput::make('priority')->label('Приоритет')->numeric()->columnSpan(2),
                        DatePicker::make('published_at')->label('Дата публикации')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Компания')->searchable()->sortable()->weight('bold')->limit(35),
                TextColumn::make('contact_person')->label('Контакт')->searchable(),
                TextColumn::make('city')->label('Город')->searchable()->sortable(),
                TextColumn::make('phone')->label('Телефон')->badge()->color('gray')->limit(15),
                TextColumn::make('subscription_type')->label('Подписка')->badge()->color('primary'),
                TextColumn::make('employee_count')->label('Сотрудников')->numeric()->badge()->color('secondary'),
                TextColumn::make('daily_cost')->label('Стоимость/дн (₽)')->numeric()->badge()->color('info'),
                BadgeColumn::make('status')->label('Статус')->colors(['success' => 'active', 'danger' => 'inactive', 'warning' => 'trial']),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('verified')->label('Проверена')->colors(['success' => true, 'gray' => false]),
                TextColumn::make('priority')->label('Приоритет')->numeric()->badge(),
                TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('subscription_type')->options(['daily' => 'Ежедневная', 'weekly' => 'Еженедельная']),
                SelectFilter::make('status')->options(['active' => 'Активна', 'inactive' => 'Неактивна']),
                Filter::make('is_active')->query(fn (Builder $q) => $q->where('is_active', true)),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListOfficeCatering::route('/'),
                'create' => Pages\CreateOfficeCatering::route('/create'),
                'edit' => Pages\EditOfficeCatering::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

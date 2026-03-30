<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShops;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MeatShopsResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = MeatShop::class;
        protected static ?string $navigationIcon = 'heroicon-o-sparkles';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название лавки')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                        TextInput::make('website')->label('Веб-сайт')->url()->columnSpan(1),
                        Select::make('type')->label('Тип')->options([
                            'butcher_shop' => 'Лавка',
                            'farm_direct' => 'Ферма напрямую',
                            'wholesale' => 'Оптовая',
                            'restaurant_supplier' => 'Поставщик ресторанам'
                        ])->required()->columnSpan(1),
                    ]),

                Section::make('Адрес и геолокация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->columnSpan(2),
                        TextInput::make('city')->label('Город')->required()->columnSpan(1),
                        TextInput::make('zip_code')->label('Почтовый код')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('Описание и специализация')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                        TagsInput::make('specializations')->label('Специализация (говядина, свинина, курица, дичь)')->columnSpan('full'),
                    ]),

                Section::make('Ассортимент мяса')
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_beef')->label('Говядина')->columnSpan(1),
                        Toggle::make('has_pork')->label('Свинина')->columnSpan(1),
                        Toggle::make('has_chicken')->label('Курица')->columnSpan(1),
                        Toggle::make('has_lamb')->label('Баранина')->columnSpan(1),
                        Toggle::make('has_game')->label('Дичь')->columnSpan(1),
                        Toggle::make('has_rabbit')->label('Кролик')->columnSpan(1),
                        Toggle::make('organic_certified')->label('Органическое мясо (сертифицировано)')->columnSpan(2),
                        TagsInput::make('meat_types')->label('Виды мяса')->columnSpan(2),
                    ]),

                Section::make('Обработка и разделка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('offers_custom_cutting')->label('Кастомная разделка')->columnSpan(1),
                        Toggle::make('offers_vacuum_packing')->label('Вакуумная упаковка')->columnSpan(1),
                        Toggle::make('offers_marinating')->label('Маринование')->columnSpan(1),
                        Toggle::make('offers_smoking')->label('Копчение')->columnSpan(1),
                        TextInput::make('min_order_weight')->label('Минимальный заказ (кг)')->numeric()->columnSpan(1),
                        TextInput::make('max_order_weight')->label('Максимум за раз (кг)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Режим работы и доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours')->label('Рабочие часы')->placeholder('08:00-19:00')->columnSpan(2),
                        Toggle::make('has_delivery')->label('Доставка')->columnSpan(1),
                        TextInput::make('delivery_time_min')->label('Минимальное время доставки (мин)')->numeric()->columnSpan(1),
                        TextInput::make('min_order_for_delivery')->label('Минимальный заказ для доставки (₽)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_area_km')->label('Зона доставки (км)')->numeric()->columnSpan(1),
                        Toggle::make('has_cold_chain')->label('Холодная цепь')->columnSpan(1),
                    ]),

                Section::make('Боксы и подписки')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_subscription_boxes')->label('Боксы по подписке')->columnSpan(2),
                        TextInput::make('weekly_box_price')->label('Цена недельного бокса (₽)')->numeric()->columnSpan(1),
                        TextInput::make('weekly_box_weight')->label('Вес недельного бокса (кг)')->numeric()->columnSpan(1),
                        TagsInput::make('box_types')->label('Типы боксов')->columnSpan(2),
                    ]),

                Section::make('Сертификация и документы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('vet_certificate')->label('Ветеринарный сертификат (PDF)')->acceptedFileTypes(['application/pdf'])->directory('meat-certs')->columnSpan(1),
                        FileUpload::make('hygiene_certificate')->label('Сертификат гигиены (PDF)')->acceptedFileTypes(['application/pdf'])->directory('meat-certs')->columnSpan(1),
                        FileUpload::make('organic_certificate')->label('Органический сертификат (PDF)')->acceptedFileTypes(['application/pdf'])->directory('meat-certs')->columnSpan(1),
                        DatePicker::make('vet_cert_expiry')->label('Срок действия ветсертификата')->columnSpan(1),
                    ]),

                Section::make('Работники и оборудование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('butcher_count')->label('Количество мясников')->numeric()->columnSpan(1),
                        TextInput::make('daily_production_kg')->label('Суточная производственность (кг)')->numeric()->columnSpan(1),
                        Toggle::make('has_cold_storage')->label('Холодильник/Морозильник')->columnSpan(1),
                        Toggle::make('has_smoking_facility')->label('Коптильня')->columnSpan(1),
                    ]),

                Section::make('Контакты и социальные сети')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('whatsapp')->label('WhatsApp')->tel()->columnSpan(1),
                        TextInput::make('telegram')->label('Telegram')->columnSpan(1),
                        TextInput::make('instagram')->label('Instagram')->url()->columnSpan(1),
                        TextInput::make('vk')->label('VK')->url()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('logo')->label('Логотип')->image()->directory('meat-logos'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('meat-gallery')->columnSpan('full'),
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
                        DatePicker::make('published_at')->label('Публикация')->columnSpan(1),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Лавка')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('city')->label('Город')->searchable()->sortable(),
                TextColumn::make('type')->label('Тип')->badge()->color('info'),
                TextColumn::make('phone')->label('Телефон')->badge()->color('gray')->limit(18),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('verified')->label('Проверена')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранная')->colors(['warning' => true, 'gray' => false]),
                BadgeColumn::make('has_delivery')->label('Доставка')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('organic_certified')->label('Органическое')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('has_subscription_boxes')->label('Боксы')->colors(['secondary' => true, 'gray' => false]),
                TextColumn::make('priority')->label('Приоритет')->numeric()->sortable()->badge(),
                TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('type')->options([
                    'butcher_shop' => 'Лавка',
                    'farm_direct' => 'Ферма напрямую',
                    'wholesale' => 'Оптовая',
                    'restaurant_supplier' => 'Поставщик ресторанам'
                ]),
                Filter::make('is_active')->query(fn (Builder $q) => $q->where('is_active', true)),
                Filter::make('has_delivery')->query(fn (Builder $q) => $q->where('has_delivery', true))->label('С доставкой'),
                Filter::make('organic')->query(fn (Builder $q) => $q->where('organic_certified', true))->label('Органическое'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMeatShops::route('/'),
                'create' => Pages\CreateMeatShops::route('/create'),
                'edit' => Pages\EditMeatShops::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

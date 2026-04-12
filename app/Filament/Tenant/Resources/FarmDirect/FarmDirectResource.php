<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmDirect;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class FarmDirectResource extends Resource
{

    protected static ?string $model = Farm::class;
        protected static ?string $navigationIcon = 'heroicon-o-leaf';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название фермы')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                        TextInput::make('website')->label('Веб-сайт')->url()->columnSpan(1),
                        Select::make('farm_type')->label('Тип фермы')->options([
                            'vegetable' => 'Овощи',
                            'fruit' => 'Фрукты',
                            'meat' => 'Мясо',
                            'dairy' => 'Молочная',
                            'mixed' => 'Смешанная'
                        ])->required()->columnSpan(1),
                    ]),

                Section::make('Адрес и геолокация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->columnSpan(2),
                        TextInput::make('city')->label('Город/Район')->required()->columnSpan(1),
                        TextInput::make('zip_code')->label('Почтовый код')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Ассортимент')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('products')->label('Основные продукты')->columnSpan(2),
                        Toggle::make('is_organic')->label('Органическое сертифицировано')->columnSpan(1),
                        Toggle::make('is_eco')->label('Эко-производство')->columnSpan(1),
                        Toggle::make('uses_gmo')->label('Использует ГМО')->columnSpan(1),
                        Toggle::make('has_pesticides')->label('Использует пестициды')->columnSpan(1),
                    ]),

                Section::make('Сертификация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('organic_certificate')->label('Органический сертификат (PDF)')->acceptedFileTypes(['application/pdf'])->directory('farm-certs')->columnSpan(1),
                        FileUpload::make('other_certificates')->label('Другие сертификаты (PDF)')->acceptedFileTypes(['application/pdf'])->directory('farm-certs')->columnSpan(1),
                        DatePicker::make('cert_expiry')->label('Срок действия сертификата')->columnSpan(2),
                    ]),

                Section::make('Доставка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_delivery')->label('Доставка')->columnSpan(2),
                        TextInput::make('delivery_radius_km')->label('Радиус доставки (км)')->numeric()->columnSpan(1),
                        TextInput::make('delivery_time_min')->label('Минимальное время (дней)')->numeric()->columnSpan(1),
                        TextInput::make('min_order_amount')->label('Минимальный заказ (₽)')->numeric()->columnSpan(1),
                        Toggle::make('has_subscription')->label('Боксы по подписке')->columnSpan(1),
                    ]),

                Section::make('Режим работы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('working_hours')->label('Рабочие часы')->placeholder('09:00-18:00')->columnSpan(2),
                        TextInput::make('pickup_start_hour')->label('Забор с утра (часов)')->numeric()->columnSpan(1),
                        TextInput::make('pickup_end_hour')->label('Забор до (часов)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Контакты')
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
                        FileUpload::make('logo')->label('Логотип')->image()->directory('farm-logos'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('farm-gallery')->columnSpan('full'),
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
                ImageColumn::make('logo')->label('Логотип')->size(50),
                TextColumn::make('name')->label('Ферма')->searchable()->sortable()->weight('bold')->limit(40),
                TextColumn::make('city')->label('Город')->searchable()->sortable(),
                TextColumn::make('farm_type')->label('Тип')->badge()->color('info'),
                TextColumn::make('phone')->label('Телефон')->badge()->color('gray')->limit(18),
                BadgeColumn::make('is_active')->label('Активна')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_organic')->label('Органическая')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('has_delivery')->label('Доставка')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранная')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('priority')->label('Приоритет')->numeric()->badge(),
            ])->filters([
                SelectFilter::make('farm_type')->options([
                    'vegetable' => 'Овощи',
                    'fruit' => 'Фрукты',
                    'meat' => 'Мясо',
                    'dairy' => 'Молочная',
                    'mixed' => 'Смешанная'
                ]),
                Filter::make('is_organic')->query(fn (Builder $q) => $q->where('is_organic', true))->label('Органическая'),
                Filter::make('has_delivery')->query(fn (Builder $q) => $q->where('has_delivery', true))->label('С доставкой'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFarmDirect::route('/'),
                'create' => Pages\CreateFarmDirect::route('/create'),
                'edit' => Pages\EditFarmDirect::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use Illuminate\Database\Eloquent\Builder;
final class RealEstateResource extends Resource
{

    protected static ?string $model = Property::class;
        protected static ?string $navigationIcon = 'heroicon-o-home';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('property_code')->label('Код объекта')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('type')->label('Тип')->options([
                            'apartment' => 'Квартира',
                            'house' => 'Дом',
                            'land' => 'Участок',
                            'commercial' => 'Коммерческая',
                            'industrial' => 'Промышленная'
                        ])->required()->columnSpan(1),
                        TextInput::make('address')->label('Адрес')->required()->maxLength(500)->columnSpan(2),
                    ]),

                Section::make('Геоданные')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('city')->label('Город')->required()->columnSpan(1),
                        TextInput::make('district')->label('Район')->columnSpan(1),
                        TextInput::make('street')->label('Улица')->columnSpan(1),
                        TextInput::make('house_number')->label('№ дома')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                    ]),

                Section::make('Характеристики помещения')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_area')->label('Общая площадь (м²)')->numeric()->required()->columnSpan(1),
                        TextInput::make('living_area')->label('Жилая площадь (м²)')->numeric()->columnSpan(1),
                        TextInput::make('rooms_count')->label('Количество комнат')->numeric()->columnSpan(1),
                        TextInput::make('bedrooms')->label('Спальни')->numeric()->columnSpan(1),
                        TextInput::make('bathrooms')->label('Санузлы')->numeric()->columnSpan(1),
                        TextInput::make('floor')->label('Этаж')->numeric()->columnSpan(1),
                        TextInput::make('total_floors')->label('Всего этажей')->numeric()->columnSpan(1),
                        Select::make('construction_type')->label('Тип постройки')->options([
                            'brick' => 'Кирпич',
                            'panel' => 'Панель',
                            'wood' => 'Дерево',
                            'monolith' => 'Монолит'
                        ])->columnSpan(1),
                    ]),

                Section::make('Ремонт и состояние')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('repair_status')->label('Состояние ремонта')->options([
                            'needs_repair' => 'Требует ремонта',
                            'partial_repair' => 'Частичный ремонт',
                            'good' => 'Хороший',
                            'excellent' => 'Отличный'
                        ])->columnSpan(1),
                        TextInput::make('year_built')->label('Год постройки')->numeric()->columnSpan(1),
                        TextInput::make('year_renovated')->label('Год последнего ремонта')->numeric()->columnSpan(1),
                        Toggle::make('furnished')->label('Меблировано')->columnSpan(1),
                        Toggle::make('equipped_kitchen')->label('Оборудованная кухня')->columnSpan(1),
                        TagsInput::make('amenities')->label('Удобства')->columnSpan(2),
                    ]),

                Section::make('Стоимость (продажа)')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('sale_price')->label('Цена продажи (₽)')->numeric()->columnSpan(1),
                        TextInput::make('price_per_sqm')->label('Цена за м² (₽)')->numeric()->columnSpan(1),
                        Toggle::make('for_sale')->label('На продажу')->default(false)->columnSpan(1),
                        TextInput::make('commission_percent')->label('Комиссия агента (%)')->numeric()->columnSpan(1),
                        Textarea::make('sale_terms')->label('Условия продажи')->maxLength(500)->rows(2)->columnSpan(2),
                    ]),

                Section::make('Стоимость (аренда)')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('rental_price')->label('Месячная аренда (₽)')->numeric()->columnSpan(1),
                        TextInput::make('deposit_amount')->label('Размер залога (₽)')->numeric()->columnSpan(1),
                        Toggle::make('for_rent')->label('Сдаётся в аренду')->default(false)->columnSpan(1),
                        TextInput::make('min_lease_months')->label('Минимум месяцев')->numeric()->columnSpan(1),
                        TextInput::make('max_lease_months')->label('Максимум месяцев')->numeric()->columnSpan(1),
                        Textarea::make('rental_terms')->label('Условия аренды')->maxLength(500)->rows(2)->columnSpan(2),
                    ]),

                Section::make('Коммунальные платежи')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('utilities_cost')->label('Коммунальные (₽/мес)')->numeric()->columnSpan(1),
                        Toggle::make('utilities_included')->label('В цене подключение')->columnSpan(1),
                        Toggle::make('has_gas')->label('Газ')->columnSpan(1),
                        Toggle::make('has_water')->label('Водопровод')->columnSpan(1),
                        Toggle::make('has_sewage')->label('Канализация')->columnSpan(1),
                        Toggle::make('has_electricity')->label('Электричество')->columnSpan(1),
                        Toggle::make('has_heating')->label('Отопление')->columnSpan(1),
                        Toggle::make('has_internet')->label('Интернет')->columnSpan(1),
                    ]),

                Section::make('Инфраструктура и метро')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('metro_distance_m')->label('Расстояние до метро (м)')->numeric()->columnSpan(1),
                        TextInput::make('metro_time_minutes')->label('Время до метро (мин)')->numeric()->columnSpan(1),
                        TextInput::make('school_distance_m')->label('До школы (м)')->numeric()->columnSpan(1),
                        TextInput::make('hospital_distance_m')->label('До больницы (м)')->numeric()->columnSpan(1),
                        TextInput::make('park_distance_m')->label('До парка (м)')->numeric()->columnSpan(1),
                        Toggle::make('shopping_nearby')->label('Магазины рядом')->columnSpan(1),
                        Toggle::make('public_transport')->label('Общественный транспорт')->columnSpan(1),
                        Toggle::make('parking_available')->label('Парковка')->columnSpan(1),
                    ]),

                Section::make('Документы и юридия')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('property_status')->label('Статус недвижимости')->options([
                            'ready' => 'Готова',
                            'under_construction' => 'Строящаяся',
                            'planning' => 'Планируется'
                        ])->columnSpan(1),
                        Toggle::make('has_legal_deed')->label('Имеется свидетельство')->columnSpan(1),
                        Toggle::make('has_mortgage')->label('Ипотека возможна')->columnSpan(1),
                        TextInput::make('cadastral_number')->label('Кадастровый номер')->columnSpan(1),
                        FileUpload::make('title_deed')->label('Свидетельство о собственности')->acceptedFileTypes(['application/pdf'])->directory('real-estate-docs'),
                    ]),

                Section::make('Просмотры и бронирование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('viewing_price')->label('Цена просмотра (₽)')->numeric()->columnSpan(1),
                        Toggle::make('accepts_viewings')->label('Принимает просмотры')->default(true)->columnSpan(1),
                        TextInput::make('available_viewings_per_week')->label('Просмотров в неделю')->numeric()->columnSpan(1),
                        TextInput::make('min_notice_hours')->label('Уведомление за (часов)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('real-estate'),
                        FileUpload::make('gallery')->label('Галерея')->multiple()->image()->directory('real-estate-gallery')->columnSpan('full'),
                        TextInput::make('video_tour_url')->label('3D-тур URL')->url(),
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
                ImageColumn::make('main_image')->label('Фото')->size(40),
                TextColumn::make('address')->label('Адрес')->searchable()->sortable()->weight('bold')->limit(30),
                TextColumn::make('type')->label('Тип')->badge()->color('info'),
                TextColumn::make('city')->label('Город')->searchable(),
                TextColumn::make('total_area')->label('Площадь (м²)')->numeric(),
                TextColumn::make('price_per_sqm')->label('Цена м² (₽)')->numeric()->badge()->color('success'),
                TextColumn::make('rooms_count')->label('Комнаты')->numeric(),
                BadgeColumn::make('for_sale')->label('Продажа')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('for_rent')->label('Аренда')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('has_mortgage')->label('Ипотека')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('property_code')->label('Код')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('type')->options([
                    'apartment' => 'Квартира',
                    'house' => 'Дом',
                    'land' => 'Участок',
                    'commercial' => 'Коммерческая',
                ]),
                Filter::make('for_sale')->query(fn (Builder $q) => $q->where('for_sale', true))->label('На продажу'),
                Filter::make('for_rent')->query(fn (Builder $q) => $q->where('for_rent', true))->label('В аренду'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('published_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListRealEstate::route('/'),
                'create' => Pages\CreateRealEstate::route('/create'),
                'edit' => Pages\EditRealEstate::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

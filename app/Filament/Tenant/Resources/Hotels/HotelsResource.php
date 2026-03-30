<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HotelsResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Hotel::class;
        protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->label('Название')->required()->maxLength(255)->columnSpan(2),
                        TextInput::make('hotel_code')->label('Код отеля')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('star_rating')->label('Звёздность')->options([
                            1 => '1 звезда',
                            2 => '2 звезды',
                            3 => '3 звезды',
                            4 => '4 звезды',
                            5 => '5 звёзд'
                        ])->columnSpan(1),
                        TextInput::make('brand')->label('Бренд/Сеть')->maxLength(100)->columnSpan(1),
                    ]),

                Section::make('Местоположение и контакты')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')->label('Адрес')->required()->maxLength(500)->columnSpan(2),
                        TextInput::make('city')->label('Город')->maxLength(100)->columnSpan(1),
                        TextInput::make('region')->label('Область')->maxLength(100)->columnSpan(1),
                        TextInput::make('postal_code')->label('Почтовый индекс')->columnSpan(1),
                        TextInput::make('country')->label('Страна')->columnSpan(1),
                        TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                        TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                        TextInput::make('phone')->label('Телефон')->tel()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->columnSpan(1),
                        TextInput::make('website')->label('Сайт')->url()->columnSpan(1),
                    ]),

                Section::make('Описание')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Номерной фонд')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_rooms')->label('Всего номеров')->numeric()->required()->columnSpan(1),
                        TextInput::make('suites_count')->label('Люксов')->numeric()->columnSpan(1),
                        TextInput::make('double_rooms')->label('Двухместных')->numeric()->columnSpan(1),
                        TextInput::make('single_rooms')->label('Одноместных')->numeric()->columnSpan(1),
                        TextInput::make('family_rooms')->label('Семейных')->numeric()->columnSpan(1),
                        TextInput::make('accessible_rooms')->label('Для инвалидов')->numeric()->columnSpan(1),
                        TextInput::make('avg_room_price')->label('Средняя цена номера (₽)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Удобства в номере')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('has_ac')->label('Кондиционер'),
                        Toggle::make('has_heating')->label('Отопление'),
                        Toggle::make('has_wifi')->label('WiFi'),
                        Toggle::make('has_tv')->label('Телевизор'),
                        Toggle::make('has_phone')->label('Телефон'),
                        Toggle::make('has_safe')->label('Сейф'),
                        Toggle::make('has_mini_bar')->label('Мини-бар'),
                        Toggle::make('has_kitchenette')->label('Кухня'),
                        Toggle::make('has_balcony')->label('Балкон'),
                    ]),

                Section::make('Удобства в отеле')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Toggle::make('has_restaurant')->label('Ресторан'),
                        Toggle::make('has_bar')->label('Бар'),
                        Toggle::make('has_spa')->label('СПА'),
                        Toggle::make('has_gym')->label('Тренажёрный зал'),
                        Toggle::make('has_pool')->label('Бассейн'),
                        Toggle::make('has_parking')->label('Парковка'),
                        Toggle::make('has_conference_room')->label('Конференц-залы'),
                        Toggle::make('has_business_center')->label('Бизнес-центр'),
                        Toggle::make('has_concierge')->label('Консьерж'),
                    ]),

                Section::make('Завтрак и питание')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('breakfast_type')->label('Завтрак')->options([
                            'not_included' => 'Не включён',
                            'continental' => 'Continental',
                            'full_american' => 'Full American',
                            'buffet' => 'Буфет'
                        ])->columnSpan(1),
                        TextInput::make('breakfast_price')->label('Цена завтрака (₽)')->numeric()->columnSpan(1),
                        Toggle::make('offers_vegan')->label('Веган меню')->columnSpan(1),
                        Toggle::make('offers_vegetarian')->label('Вегетарианское меню')->columnSpan(1),
                    ]),

                Section::make('Размещение и сроки')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('check_in_time')->label('Заселение (время)')->columnSpan(1),
                        TextInput::make('check_out_time')->label('Выселение (время)')->columnSpan(1),
                        TextInput::make('min_stay_days')->label('Минимальный срок пребывания')->numeric()->columnSpan(1),
                        TextInput::make('payout_delay_days')->label('Выплата бизнесу (дней)')->numeric()->columnSpan(1),
                        Toggle::make('accepts_late_checkout')->label('Возможно позднее выселение')->columnSpan(1),
                        Toggle::make('free_cancellation')->label('Бесплатная отмена')->columnSpan(1),
                    ]),

                Section::make('Оценки и аналитика')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('avg_rating')->label('Средний рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                        TextInput::make('occupancy_rate')->label('Заполняемость (%)')->numeric()->columnSpan(1),
                        TextInput::make('repeat_guest_percent')->label('% постоянных гостей')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('main_image')->label('Главное фото')->image()->directory('hotels-main'),
                        FileUpload::make('lobby_image')->label('Холл')->image()->directory('hotels-lobby'),
                        FileUpload::make('room_image')->label('Номер')->image()->directory('hotels-room'),
                        FileUpload::make('gallery')->label('Галерея 360°')->multiple()->image()->directory('hotels-gallery')->columnSpan('full'),
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
                ImageColumn::make('main_image')->label('Фото')->size(50),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold')->limit(35),
                TextColumn::make('city')->label('Город')->searchable(),
                TextColumn::make('star_rating')->label('Звёзды')->badge()->color('warning'),
                TextColumn::make('total_rooms')->label('Номеров')->numeric(),
                TextColumn::make('avg_room_price')->label('Средняя цена (₽)')->numeric()->badge()->color('success'),
                TextColumn::make('avg_rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('info'),
                BadgeColumn::make('has_pool')->label('Бассейн')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('has_spa')->label('СПА')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('phone')->label('Телефон')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('star_rating')->options([
                    1 => '1 звезда',
                    2 => '2 звезды',
                    3 => '3 звезды',
                    4 => '4 звезды',
                    5 => '5 звёзд'
                ]),
                Filter::make('has_pool')->query(fn (Builder $q) => $q->where('has_pool', true))->label('С бассейном'),
                Filter::make('has_spa')->query(fn (Builder $q) => $q->where('has_spa', true))->label('С СПА'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListHotels::route('/'),
                'create' => Pages\CreateHotels::route('/create'),
                'edit' => Pages\EditHotels::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Photography;
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
final class PhotographyResource extends Resource
{

    protected static ?string $model = Photographer::class;
        protected static ?string $navigationIcon = 'heroicon-o-camera';
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
                        TextInput::make('photographer_code')->label('Код фотографа')->unique(ignoreRecord: true)->columnSpan(1),
                        Select::make('specialization')->label('Специализация')->options([
                            'wedding' => 'Свадьбы',
                            'portrait' => 'Портреты',
                            'event' => 'События',
                            'product' => 'Товары',
                            'real_estate' => 'Недвижимость',
                            'fashion' => 'Мода',
                            'food' => 'Еда',
                            'lifestyle' => 'Lifestyle'
                        ])->required()->columnSpan(1),
                        TextInput::make('studio_name')->label('Название студии/компании')->maxLength(255)->columnSpan(1),
                    ]),

                Section::make('Контактная информация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                        TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                        TextInput::make('website')->label('Сайт')->url()->columnSpan(1),
                        TextInput::make('instagram')->label('Instagram')->columnSpan(1),
                        TextInput::make('city')->label('Город')->maxLength(100)->columnSpan(1),
                        TextInput::make('address')->label('Адрес студии')->maxLength(500)->columnSpan(1),
                    ]),

                Section::make('О фотографе')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_bio')->label('Краткая биография')->maxLength(500)->rows(3),
                        RichEditor::make('full_bio')->label('Полная биография')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Опыт и образование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('years_experience')->label('Опыт (лет)')->numeric()->columnSpan(1),
                        TextInput::make('total_sessions')->label('Проведено сессий')->numeric()->columnSpan(1),
                        DatePicker::make('start_year')->label('Год начала карьеры')->columnSpan(1),
                        TextInput::make('education')->label('Образование')->maxLength(255)->columnSpan(1),
                        TagsInput::make('certifications')->label('Сертификаты')->columnSpan(2),
                        TagsInput::make('awards')->label('Награды')->columnSpan(2),
                    ]),

                Section::make('Услуги и пакеты')
                    ->collapsed()
                    ->schema([
                        Repeater::make('service_packages')->label('Пакеты услуг')
                            ->schema([
                                TextInput::make('package_name')->label('Название')->required(),
                                TextInput::make('price')->label('Цена (₽)')->numeric()->required(),
                                TextInput::make('duration_hours')->label('Длительность (часов)')->numeric(),
                                TextInput::make('photos_count')->label('Кол-во фото')->numeric(),
                                Textarea::make('description')->label('Описание')->maxLength(500)->rows(2),
                            ])->columnSpan('full'),
                    ]),

                Section::make('Цены и условия')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('min_booking_price')->label('Минимальная сумма заказа (₽)')->numeric()->columnSpan(1),
                        TextInput::make('deposit_percent')->label('Размер предоплаты (%)')->numeric()->columnSpan(1),
                        TextInput::make('cancellation_fee_percent')->label('Штраф отмены (%)')->numeric()->columnSpan(1),
                        TextInput::make('revision_count')->label('Включено ревизий')->numeric()->columnSpan(1),
                        Toggle::make('offers_rush_booking')->label('Срочное бронирование')->columnSpan(1),
                        TextInput::make('rush_booking_multiplier')->label('Множитель срочности')->numeric()->step(0.1)->columnSpan(1),
                    ]),

                Section::make('Оборудование')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('camera_models')->label('Камеры')->columnSpan(2),
                        TagsInput::make('lens_types')->label('Объективы')->columnSpan(2),
                        Toggle::make('has_studio')->label('Собственная студия')->columnSpan(1),
                        Toggle::make('has_drone')->label('Дрон')->columnSpan(1),
                        Toggle::make('has_lighting_kit')->label('Профессиональное освещение')->columnSpan(1),
                        Toggle::make('has_backup_camera')->label('Запасная камера')->columnSpan(1),
                    ]),

                Section::make('Услуги редактирования')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('editing_time_days')->label('Время редактирования (дней)')->numeric()->columnSpan(1),
                        Toggle::make('includes_editing')->label('Редактирование включено')->columnSpan(1),
                        Toggle::make('offers_unlimited_edits')->label('Бесплатные правки')->columnSpan(1),
                        TextInput::make('revision_limit')->label('Лимит ревизий')->numeric()->columnSpan(1),
                        TextInput::make('extra_edit_price')->label('Цена за дополнительную ревизию (₽)')->numeric()->columnSpan(1),
                        Toggle::make('offers_album_design')->label('Дизайн альбома')->columnSpan(1),
                    ]),

                Section::make('Галерея и портфолио')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('portfolio_url')->label('URL портфолио')->url()->columnSpan(2),
                        Toggle::make('has_online_gallery')->label('Онлайн-галерея')->columnSpan(1),
                        Toggle::make('gallery_password_protected')->label('Защита паролем')->columnSpan(1),
                        TextInput::make('gallery_view_limit')->label('Время доступа (дней)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Рейтинг и отзывы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                        TextInput::make('repeat_client_percent')->label('% постоянных клиентов')->numeric()->columnSpan(1),
                        TextInput::make('avg_session_rating')->label('Средний рейтинг сессии')->numeric(decimals: 1)->columnSpan(1),
                    ]),

                Section::make('Доступность')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('available_weekends')->label('Выходные')->columnSpan(1),
                        Toggle::make('available_evenings')->label('Вечера')->columnSpan(1),
                        Toggle::make('available_travel')->label('Выезд за город')->columnSpan(1),
                        TextInput::make('travel_cost_per_km')->label('Стоимость выезда (₽/км)')->numeric()->columnSpan(1),
                        TextInput::make('travel_min_distance')->label('Минимальное расстояние (км)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('profile_photo')->label('Фото профиля')->image()->directory('photo-profile'),
                        FileUpload::make('portfolio_samples')->label('Примеры портфолио')->multiple()->image()->directory('photo-portfolio')->columnSpan('full'),
                        FileUpload::make('studio_image')->label('Фото студии')->image()->directory('photo-studio'),
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
                TextColumn::make('specialization')->label('Специализация')->badge()->color('info'),
                TextColumn::make('city')->label('Город')->searchable(),
                TextColumn::make('rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning')->sortable(),
                TextColumn::make('total_sessions')->label('Сессий')->numeric(),
                TextColumn::make('min_booking_price')->label('Мин. цена (₽)')->numeric()->badge()->color('success'),
                BadgeColumn::make('has_studio')->label('Студия')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('available_travel')->label('Выезд')->colors(['secondary' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('phone')->label('Телефон')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('specialization')->options([
                    'wedding' => 'Свадьбы',
                    'portrait' => 'Портреты',
                    'event' => 'События',
                    'product' => 'Товары',
                ]),
                Filter::make('has_studio')->query(fn (Builder $q) => $q->where('has_studio', true))->label('Со студией'),
                Filter::make('travel')->query(fn (Builder $q) => $q->where('available_travel', true))->label('С выездом'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('rating', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListPhotography::route('/'),
                'create' => Pages\CreatePhotography::route('/create'),
                'edit' => Pages\EditPhotography::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

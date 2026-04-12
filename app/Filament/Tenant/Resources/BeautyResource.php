<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class BeautyResource extends Resource
{

    protected static ?string $model = BeautySalon::class;

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationLabel = 'Салоны красоты';

        protected static ?string $navigationGroup = 'Beauty';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Информация о салоне красоты')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название салона')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('logo')
                            ->label('Логотип')
                            ->image()
                            ->directory('beauty/logos')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Контакты и местоположение')
                    ->description('Адрес, телефон, сайт')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->maxLength(500),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('city')
                                ->label('Город')
                                ->required(),

                            Forms\Components\TextInput::make('postal_code')
                                ->label('Почтовый индекс'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('phone')
                                ->label('Телефон')
                                ->tel(),

                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email(),

                            Forms\Components\TextInput::make('website')
                                ->label('Веб-сайт')
                                ->url(),

                            Forms\Components\TextInput::make('instagram')
                                ->label('Instagram'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('latitude')
                                ->label('Широта')
                                ->numeric(),

                            Forms\Components\TextInput::make('longitude')
                                ->label('Долгота')
                                ->numeric(),
                        ]),
                    ]),

                Forms\Components\Section::make('Характеристики и услуги')
                    ->description('Специализация и услуги')
                    ->schema([
                        Forms\Components\Select::make('specializations')
                            ->label('Специализация')
                            ->multiple()
                            ->options([
                                'hair' => 'Парикмахерские услуги',
                                'makeup' => 'Макияж',
                                'nails' => 'Маникюр/Педикюр',
                                'massage' => 'Массаж',
                                'facials' => 'Косметология лица',
                                'waxing' => 'Восковая депиляция',
                                'tattoobrows' => 'Татуаж бровей',
                                'lashes' => 'Наращивание ресниц',
                            ]),

                        Forms\Components\Repeater::make('services')
                            ->label('Услуги')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Название услуги')
                                        ->required(),

                                    Forms\Components\TextInput::make('duration_minutes')
                                        ->label('Длительность (мин)')
                                        ->numeric(),

                                    Forms\Components\TextInput::make('price')
                                        ->label('Цена (₽)')
                                        ->numeric()
                                        ->required(),

                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Активна'),
                                ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Рейтинг и отзывы')
                    ->description('Оценки салона')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('rating')
                                ->label('Рейтинг (0-5)')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('review_count')
                                ->label('Отзывов')
                                ->numeric()
                                ->disabled(),

                            Forms\Components\TextInput::make('average_review_score')
                                ->label('Средний балл')
                                ->numeric()
                                ->disabled(),
                        ]),
                    ]),

                Forms\Components\Section::make('Расписание')
                    ->description('Режим работы')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TimePicker::make('opening_time')
                                ->label('Время открытия'),

                            Forms\Components\TimePicker::make('closing_time')
                                ->label('Время закрытия'),
                        ]),

                        Forms\Components\Select::make('work_days')
                            ->label('Дни работы')
                            ->multiple()
                            ->options([
                                'monday' => 'Понедельник',
                                'tuesday' => 'Вторник',
                                'wednesday' => 'Среда',
                                'thursday' => 'Четверг',
                                'friday' => 'Пятница',
                                'saturday' => 'Суббота',
                                'sunday' => 'Воскресенье',
                            ])
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Управление')
                    ->description('Статус и параметры')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('status')
                                ->label('Статус')
                                ->options([
                                    'active' => 'Активен',
                                    'inactive' => 'Неактивен',
                                    'pending' => 'Ожидает проверки',
                                    'suspended' => 'Заблокирован',
                                ])
                                ->default('pending')
                                ->required(),

                            Forms\Components\Toggle::make('is_verified')
                                ->label('Проверен')
                                ->default(false),

                            Forms\Components\TextInput::make('commission_percent')
                                ->label('Комиссия платформы (%)')
                                ->numeric()
                                ->default(14),
                        ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Внутренние примечания')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListBeauty::route('/'),
                'create' => Pages\CreateBeauty::route('/create'),
                'edit' => Pages\EditBeauty::route('/{record}/edit'),
                'view' => Pages\ViewBeauty::route('/{record}'),
            ];
        }
}

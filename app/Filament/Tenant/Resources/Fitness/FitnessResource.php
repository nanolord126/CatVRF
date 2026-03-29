<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fitness;

use App\Domains\Fitness\Models\Gym;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

final class FitnessResource extends Resource
{
    protected static ?string $model = Gym::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Вертикали';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
            Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('gym_code')->label('Код клуба')->unique(ignoreRecord: true)->columnSpan(1),
                    TextInput::make('name')->label('Название')->required()->maxLength(255)->columnSpan(1),
                    Select::make('type')->label('Тип')->options([
                        'gym' => 'Тренажёрный зал',
                        'yoga' => 'Йога-студия',
                        'pilates' => 'Пилатес',
                        'crossfit' => 'CrossFit',
                        'boxing' => 'Бокс',
                        'dance' => 'Танцы',
                        'swimming' => 'Бассейн',
                        'mixed' => 'Смешанный'
                    ])->required()->columnSpan(1),
                    TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                ]),

            Section::make('Адрес и контакты')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('address')->label('Адрес')->required()->maxLength(500)->columnSpan(2),
                    TextInput::make('city')->label('Город')->required()->columnSpan(1),
                    TextInput::make('district')->label('Район')->columnSpan(1),
                    TextInput::make('email')->label('Email')->email()->columnSpan(1),
                    TextInput::make('website')->label('Сайт')->url()->columnSpan(1),
                    TextInput::make('latitude')->label('Широта')->numeric()->columnSpan(1),
                    TextInput::make('longitude')->label('Долгота')->numeric()->columnSpan(1),
                ]),

            Section::make('О клубе')
                ->collapsed()
                ->schema([
                    Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                    RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                ]),

            Section::make('Помещение и оборудование')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('total_area_sqm')->label('Площадь (м²)')->numeric()->columnSpan(1),
                    TextInput::make('training_zones_count')->label('Тренировочных зон')->numeric()->columnSpan(1),
                    TextInput::make('cardio_machines')->label('Кардио-тренажёров')->numeric()->columnSpan(1),
                    TextInput::make('strength_equipment')->label('Силовых тренажёров')->numeric()->columnSpan(1),
                    TextInput::make('free_weights_kg')->label('Свободных весов (кг)')->numeric()->columnSpan(1),
                    TextInput::make('yoga_mats')->label('Йога-матов')->numeric()->columnSpan(1),
                    Toggle::make('has_pool')->label('Бассейн')->columnSpan(1),
                    Toggle::make('has_sauna')->label('Сауна')->columnSpan(1),
                    Toggle::make('has_steam_room')->label('Паровая кабина')->columnSpan(1),
                    Toggle::make('has_jacuzzi')->label('Джакузи')->columnSpan(1),
                ]),

            Section::make('Услуги и удобства')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('personal_training')->label('Личный тренинг')->columnSpan(1),
                    Toggle::make('group_classes')->label('Групповые занятия')->columnSpan(1),
                    Toggle::make('nutrition_consultation')->label('Консультация диетолога')->columnSpan(1),
                    Toggle::make('physiotherapy')->label('Физиотерапия')->columnSpan(1),
                    Toggle::make('has_cafe')->label('Кафе')->columnSpan(1),
                    Toggle::make('has_shop')->label('Магазин товаров')->columnSpan(1),
                    Toggle::make('lockers_free')->label('Бесплатные шкафчики')->columnSpan(1),
                    Toggle::make('towel_service')->label('Сервис полотенец')->columnSpan(1),
                    TagsInput::make('amenities')->label('Удобства')->columnSpan(2),
                ]),

            Section::make('Абонементы и цены')
                ->collapsed()
                ->schema([
                    Repeater::make('membership_plans')->label('Абонементы')
                        ->schema([
                            TextInput::make('plan_name')->label('Название')->required(),
                            TextInput::make('duration_days')->label('Длительность (дней)')->numeric()->required(),
                            TextInput::make('price')->label('Цена (₽)')->numeric()->required(),
                            TextInput::make('visits_limit')->label('Лимит посещений')->numeric(),
                            Textarea::make('description')->label('Описание')->maxLength(300)->rows(2),
                        ])->columnSpan('full'),
                ]),

            Section::make('График и доступность')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('opening_time')->label('Открыто')->columnSpan(1),
                    TextInput::make('closing_time')->label('Закрыто')->columnSpan(1),
                    Toggle::make('open_24_hours')->label('24/7')->columnSpan(1),
                    Toggle::make('open_weekends')->label('Выходные')->columnSpan(1),
                    TextInput::make('peak_hours_start')->label('Пик часов начало')->columnSpan(1),
                    TextInput::make('peak_hours_end')->label('Пик часов конец')->columnSpan(1),
                ]),

            Section::make('Персонал')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('total_trainers')->label('Тренеров')->numeric()->columnSpan(1),
                    TextInput::make('certified_trainers')->label('Сертифицированных')->numeric()->columnSpan(1),
                    TextInput::make('total_staff')->label('Всего сотрудников')->numeric()->columnSpan(1),
                    TagsInput::make('trainer_specializations')->label('Специализации')->columnSpan(2),
                ]),

            Section::make('Рейтинг и популярность')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('rating')->label('Рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                    TextInput::make('review_count')->label('Отзывов')->numeric()->columnSpan(1),
                    TextInput::make('active_members')->label('Активных членов')->numeric()->columnSpan(1),
                    TextInput::make('avg_daily_visits')->label('Средний дневной посещаемый')->numeric()->columnSpan(1),
                    TextInput::make('member_satisfaction')->label('Удовлетворённость (%)')->numeric()->columnSpan(1),
                    TextInput::make('retention_rate')->label('Retention rate (%)')->numeric()->columnSpan(1),
                ]),

            Section::make('Специальные программы')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('has_online_classes')->label('Онлайн-классы')->columnSpan(1),
                    Toggle::make('has_app')->label('Мобильное приложение')->columnSpan(1),
                    Toggle::make('has_nutrition_program')->label('Программа питания')->columnSpan(1),
                    Toggle::make('has_transformation_challenge')->label('Челленджи')->columnSpan(1),
                    Toggle::make('beginner_friendly')->label('Для новичков')->columnSpan(1),
                    Toggle::make('has_kids_program')->label('Детская программа')->columnSpan(1),
                ]),

            Section::make('Сертификация')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('iso_certified')->label('ISO сертифицирован')->columnSpan(1),
                    Toggle::make('health_certified')->label('Здоровый образ жизни')->columnSpan(1),
                    TextInput::make('license_number')->label('Номер лицензии')->columnSpan(1),
                    TagsInput::make('certifications')->label('Сертификаты')->columnSpan(2),
                ]),

            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('logo')->label('Логотип')->image()->directory('fitness-logo'),
                    FileUpload::make('main_image')->label('Главное фото')->image()->directory('fitness'),
                    FileUpload::make('gallery')->multiple()->image()->label('Галерея')->directory('fitness-gallery')->columnSpan('full'),
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
            TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold')->limit(30),
            TextColumn::make('type')->label('Тип')->badge()->color('info'),
            TextColumn::make('city')->label('Город')->searchable(),
            TextColumn::make('rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning')->sortable(),
            TextColumn::make('active_members')->label('Членов')->numeric(),
            TextColumn::make('total_trainers')->label('Тренеров')->numeric()->badge()->color('success'),
            BadgeColumn::make('has_pool')->label('Бассейн')->colors(['info' => true, 'gray' => false]),
            BadgeColumn::make('has_online_classes')->label('Онлайн')->colors(['secondary' => true, 'gray' => false]),
            BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
            TextColumn::make('phone')->label('Телефон')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('type')->options([
                'gym' => 'Тренажёрный зал',
                'yoga' => 'Йога-студия',
                'pilates' => 'Пилатес',
                'crossfit' => 'CrossFit',
            ]),
            Filter::make('has_pool')->query(fn (Builder $q) => $q->where('has_pool', true))->label('С бассейном'),
            Filter::make('online')->query(fn (Builder $q) => $q->where('has_online_classes', true))->label('С онлайн-классами'),
        ])->actions([ViewAction::make(), EditAction::make()])
        ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
        ->defaultSort('rating', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFitness::route('/'),
            'create' => Pages\CreateFitness::route('/create'),
            'edit' => Pages\EditFitness::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}

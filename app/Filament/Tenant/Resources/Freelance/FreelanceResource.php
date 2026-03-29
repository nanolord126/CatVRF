<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance;

use App\Domains\Freelance\Models\Freelancer;
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

final class FreelanceResource extends Resource
{
    protected static ?string $model = Freelancer::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
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
                    TextInput::make('freelancer_code')->label('Код фрилансера')->unique(ignoreRecord: true)->columnSpan(1),
                    Select::make('category')->label('Категория')->options([
                        'design' => 'Дизайн',
                        'development' => 'Разработка',
                        'writing' => 'Написание',
                        'marketing' => 'Маркетинг',
                        'translation' => 'Перевод',
                        'consulting' => 'Консультации',
                        'video' => 'Видео',
                        'music' => 'Музыка'
                    ])->required()->columnSpan(1),
                    TextInput::make('company_name')->label('Компания/Студия')->maxLength(255)->columnSpan(1),
                ]),

            Section::make('Контактная информация')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                    TextInput::make('phone')->label('Телефон')->tel()->columnSpan(1),
                    TextInput::make('website')->label('Портфолио')->url()->columnSpan(1),
                    TextInput::make('telegram')->label('Telegram')->columnSpan(1),
                    TextInput::make('country')->label('Страна')->maxLength(100)->columnSpan(1),
                    TextInput::make('timezone')->label('Часовой пояс')->columnSpan(1),
                ]),

            Section::make('О фрилансере')
                ->collapsed()
                ->schema([
                    Textarea::make('short_bio')->label('Краткая биография')->maxLength(500)->rows(3),
                    RichEditor::make('full_bio')->label('Полная биография')->maxLength(5000)->columnSpan('full'),
                ]),

            Section::make('Навыки и опыт')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TagsInput::make('skills')->label('Основные навыки')->columnSpan(2),
                    TextInput::make('years_experience')->label('Опыт (лет)')->numeric()->columnSpan(1),
                    TextInput::make('completed_jobs')->label('Завершённых проектов')->numeric()->columnSpan(1),
                    DatePicker::make('member_since')->label('Член платформы с')->columnSpan(1),
                    Textarea::make('expertise')->label('Специализация')->maxLength(1000)->rows(3)->columnSpan(2),
                ]),

            Section::make('Услуги и проекты')
                ->collapsed()
                ->schema([
                    Repeater::make('service_packages')->label('Пакеты услуг')
                        ->schema([
                            TextInput::make('service_name')->label('Название услуги')->required(),
                            TextInput::make('price')->label('Цена (₽)')->numeric()->required(),
                            TextInput::make('delivery_days')->label('Срок доставки (дней)')->numeric(),
                            Textarea::make('description')->label('Описание')->maxLength(500)->rows(2),
                        ])->columnSpan('full'),
                ]),

            Section::make('Цены и условия')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('hourly_rate')->label('Часовая ставка (₽)')->numeric()->columnSpan(1),
                    TextInput::make('min_project_price')->label('Минимальная сумма проекта (₽)')->numeric()->columnSpan(1),
                    TextInput::make('deposit_percent')->label('Размер предоплаты (%)')->numeric()->columnSpan(1),
                    TextInput::make('revision_limit')->label('Лимит правок')->numeric()->columnSpan(1),
                    Toggle::make('offers_unlimited_revisions')->label('Бесплатные правки')->columnSpan(1),
                    Toggle::make('offers_rush_delivery')->label('Срочная доставка')->columnSpan(1),
                ]),

            Section::make('Особенности и сертификаты')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TagsInput::make('certifications')->label('Сертификаты')->columnSpan(2),
                    TagsInput::make('awards')->label('Награды')->columnSpan(2),
                    Toggle::make('is_verified')->label('Проверенный фрилансер')->columnSpan(1),
                    Toggle::make('has_gold_badge')->label('Gold Member')->columnSpan(1),
                    Toggle::make('is_top_rated')->label('Top Rated')->columnSpan(1),
                    Toggle::make('is_trending')->label('Trending')->columnSpan(1),
                ]),

            Section::make('График и доступность')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Select::make('availability')->label('Доступность')->options([
                        'full_time' => 'Полный день',
                        'part_time' => 'Неполный день',
                        'hourly' => 'По часам',
                        'project_based' => 'Проектная работа'
                    ])->columnSpan(1),
                    TextInput::make('slots_available')->label('Свободных слотов')->numeric()->columnSpan(1),
                    TextInput::make('response_time_hours')->label('Время ответа (часов)')->numeric()->columnSpan(1),
                    Toggle::make('works_weekends')->label('Работает выходные')->columnSpan(1),
                ]),

            Section::make('Рейтинг и производительность')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('rating')->label('Рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                    TextInput::make('review_count')->label('Количество отзывов')->numeric()->columnSpan(1),
                    TextInput::make('on_time_delivery_percent')->label('Вовремя (%)')->numeric()->columnSpan(1),
                    TextInput::make('repeat_client_percent')->label('Постоянные клиенты (%)')->numeric()->columnSpan(1),
                    TextInput::make('avg_project_value')->label('Средняя стоимость проекта (₽)')->numeric()->columnSpan(1),
                    TextInput::make('total_earnings')->label('Общий доход (₽)')->numeric()->columnSpan(1),
                ]),

            Section::make('Портфолио')
                ->collapsed()
                ->schema([
                    TextInput::make('portfolio_url')->label('URL портфолио')->url(),
                    FileUpload::make('portfolio_samples')->label('Примеры работ')->multiple()->image()->directory('freelance-portfolio')->columnSpan('full'),
                ]),

            Section::make('Язык и общение')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TagsInput::make('languages')->label('Языки')->columnSpan(2),
                    Toggle::make('speaks_english')->label('Английский')->columnSpan(1),
                    Toggle::make('speaks_russian')->label('Русский')->columnSpan(1),
                    Toggle::make('has_interpreter')->label('Переводчик доступен')->columnSpan(1),
                ]),

            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('profile_photo')->label('Фото профиля')->image()->directory('freelance-profile'),
                    FileUpload::make('banner')->label('Баннер')->image()->directory('freelance-banner'),
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
            TextColumn::make('category')->label('Категория')->badge()->color('info'),
            TextColumn::make('rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning')->sortable(),
            TextColumn::make('completed_jobs')->label('Проектов')->numeric(),
            TextColumn::make('hourly_rate')->label('Часовая (₽)')->numeric()->badge()->color('success'),
            BadgeColumn::make('is_verified')->label('Проверен')->colors(['success' => true, 'gray' => false]),
            BadgeColumn::make('is_top_rated')->label('Top Rated')->colors(['warning' => true, 'gray' => false]),
            BadgeColumn::make('is_trending')->label('Trending')->colors(['secondary' => true, 'gray' => false]),
            BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
            TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('category')->options([
                'design' => 'Дизайн',
                'development' => 'Разработка',
                'writing' => 'Написание',
                'marketing' => 'Маркетинг',
            ]),
            Filter::make('top_rated')->query(fn (Builder $q) => $q->where('is_top_rated', true))->label('Top Rated'),
            Filter::make('verified')->query(fn (Builder $q) => $q->where('is_verified', true))->label('Проверенные'),
        ])->actions([ViewAction::make(), EditAction::make()])
        ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
        ->defaultSort('rating', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFreelance::route('/'),
            'create' => Pages\CreateFreelance::route('/create'),
            'edit' => Pages\EditFreelance::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}

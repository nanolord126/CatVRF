<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Consulting;

use App\Domains\Consulting\Models\Consultant;
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

final class ConsultingResource extends Resource
{
    protected static ?string $model = Consultant::class;
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'Вертикали';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
            Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('consultant_code')->label('Код консультанта')->unique(ignoreRecord: true)->columnSpan(1),
                    TextInput::make('full_name')->label('ФИО')->required()->maxLength(255)->columnSpan(1),
                    Select::make('specialty')->label('Специальность')->options([
                        'business' => 'Бизнес-консультирование',
                        'legal' => 'Юридическое',
                        'tax' => 'Налоговое',
                        'it' => 'IT-консультирование',
                        'hr' => 'HR-консультирование',
                        'marketing' => 'Маркетинг',
                        'financial' => 'Финансовое',
                        'career' => 'Карьерное'
                    ])->required()->columnSpan(1),
                    TextInput::make('company_name')->label('Компания/Студия')->maxLength(255)->columnSpan(1),
                ]),

            Section::make('Контактная информация')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('email')->label('Email')->email()->required()->columnSpan(1),
                    TextInput::make('phone')->label('Телефон')->tel()->required()->columnSpan(1),
                    TextInput::make('website')->label('Сайт')->url()->columnSpan(1),
                    TextInput::make('linkedin')->label('LinkedIn')->url()->columnSpan(1),
                    TextInput::make('city')->label('Город')->maxLength(100)->columnSpan(1),
                    TextInput::make('timezone')->label('Часовой пояс')->columnSpan(1),
                ]),

            Section::make('О консультанте')
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
                    TextInput::make('clients_served')->label('Обслужено клиентов')->numeric()->columnSpan(1),
                    TextInput::make('projects_completed')->label('Завершённых проектов')->numeric()->columnSpan(1),
                    DatePicker::make('career_start_date')->label('Начало карьеры')->columnSpan(1),
                    Textarea::make('education')->label('Образование')->maxLength(1000)->rows(3)->columnSpan(2),
                    TagsInput::make('certifications')->label('Сертификаты')->columnSpan(2),
                ]),

            Section::make('Услуги консультирования')
                ->collapsed()
                ->schema([
                    Repeater::make('consulting_services')->label('Услуги')
                        ->schema([
                            TextInput::make('service_name')->label('Название')->required(),
                            TextInput::make('hourly_rate')->label('Часовая ставка (₽)')->numeric()->required(),
                            TextInput::make('min_hours')->label('Минимум часов')->numeric(),
                            Textarea::make('description')->label('Описание')->maxLength(500)->rows(2),
                        ])->columnSpan('full'),
                ]),

            Section::make('Цены и условия')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('hourly_rate')->label('Часовая ставка (₽)')->numeric()->columnSpan(1),
                    TextInput::make('min_project_price')->label('Минимум проекта (₽)')->numeric()->columnSpan(1),
                    TextInput::make('deposit_percent')->label('Предоплата (%)')->numeric()->columnSpan(1),
                    TextInput::make('monthly_retainer')->label('Месячный retainer (₽)')->numeric()->columnSpan(1),
                    Toggle::make('offers_free_consultation')->label('Бесплатная консультация')->columnSpan(1),
                    TextInput::make('free_consultation_minutes')->label('Свободных минут')->numeric()->columnSpan(1),
                ]),

            Section::make('Формат работы')
                ->collapsed()
                ->columns(2)
                ->schema([
                    Toggle::make('offers_one_on_one')->label('1-на-1 консультации')->columnSpan(1),
                    Toggle::make('offers_workshops')->label('Мастер-классы')->columnSpan(1),
                    Toggle::make('offers_webinars')->label('Вебинары')->columnSpan(1),
                    Toggle::make('offers_coaching')->label('Коучинг')->columnSpan(1),
                    Toggle::make('offers_remote')->label('Удалённо')->columnSpan(1),
                    Toggle::make('offers_in_person')->label('Очно')->columnSpan(1),
                    TextInput::make('response_time_hours')->label('Время ответа (часов)')->numeric()->columnSpan(1),
                    TextInput::make('availability_hours_per_week')->label('Часов в неделю')->numeric()->columnSpan(1),
                ]),

            Section::make('Специализация и опыт')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TagsInput::make('industries')->label('Отрасли')->columnSpan(2),
                    TagsInput::make('target_audience')->label('Целевая аудитория')->columnSpan(2),
                    Textarea::make('case_studies')->label('Кейс-стади')->maxLength(2000)->rows(4)->columnSpan(2),
                ]),

            Section::make('Публикации и достижения')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('publications_count')->label('Публикаций')->numeric()->columnSpan(1),
                    TextInput::make('awards_count')->label('Наград')->numeric()->columnSpan(1),
                    TagsInput::make('published_works')->label('Опубликованные работы')->columnSpan(2),
                    TagsInput::make('awards')->label('Награды')->columnSpan(2),
                ]),

            Section::make('Рейтинг и отзывы')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TextInput::make('rating')->label('Рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                    TextInput::make('review_count')->label('Отзывов')->numeric()->columnSpan(1),
                    TextInput::make('repeat_client_percent')->label('Постоянные клиенты (%)')->numeric()->columnSpan(1),
                    TextInput::make('recommendation_rate')->label('Рекомендации (%)')->numeric()->columnSpan(1),
                ]),

            Section::make('Языки')
                ->collapsed()
                ->columns(2)
                ->schema([
                    TagsInput::make('languages')->label('Языки')->columnSpan(2),
                    Toggle::make('has_interpreter')->label('Переводчик доступен')->columnSpan(1),
                ]),

            Section::make('Портфолио и кейсы')
                ->collapsed()
                ->schema([
                    TextInput::make('portfolio_url')->label('Портфолио')->url(),
                    FileUpload::make('case_studies_documents')->label('Документы кейсов')->multiple()->acceptedFileTypes(['application/pdf'])->directory('consulting-cases'),
                ]),

            Section::make('Медиа')
                ->collapsed()
                ->schema([
                    FileUpload::make('profile_photo')->label('Фото профиля')->image()->directory('consulting-profile'),
                    FileUpload::make('background_image')->label('Фоновое изображение')->image()->directory('consulting-background'),
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
            TextColumn::make('specialty')->label('Специальность')->badge()->color('info'),
            TextColumn::make('rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning')->sortable(),
            TextColumn::make('projects_completed')->label('Проектов')->numeric(),
            TextColumn::make('hourly_rate')->label('Часовая (₽)')->numeric()->badge()->color('success'),
            TextColumn::make('years_experience')->label('Опыт (лет)')->numeric(),
            BadgeColumn::make('offers_remote')->label('Удалённо')->colors(['info' => true, 'gray' => false]),
            BadgeColumn::make('offers_free_consultation')->label('Бесплатно')->colors(['secondary' => true, 'gray' => false]),
            BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
            TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('specialty')->options([
                'business' => 'Бизнес-консультирование',
                'legal' => 'Юридическое',
                'tax' => 'Налоговое',
                'it' => 'IT',
                'hr' => 'HR',
                'marketing' => 'Маркетинг',
            ]),
            Filter::make('remote')->query(fn (Builder $q) => $q->where('offers_remote', true))->label('Удалённо'),
            Filter::make('free_consultation')->query(fn (Builder $q) => $q->where('offers_free_consultation', true))->label('Бесплатная консультация'),
        ])->actions([ViewAction::make(), EditAction::make()])
        ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
        ->defaultSort('rating', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsulting::route('/'),
            'create' => Pages\CreateConsulting::route('/create'),
            'edit' => Pages\EditConsulting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
    }
}

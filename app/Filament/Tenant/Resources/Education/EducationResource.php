<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education;

use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class EducationResource extends Resource
{

    protected static ?string $model = Course::class;
        protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
        protected static ?string $navigationGroup = 'Вертикали';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Hidden::make('tenant_id')->default(fn () => filament()->getTenant()->id),
                Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),

                Section::make('Основная информация курса')
                    ->columns(2)
                    ->schema([
                        TextInput::make('course_code')->label('Код курса')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('title')->label('Название')->required()->maxLength(255)->columnSpan(1),
                        Select::make('category')->label('Категория')->options([
                            'programming' => 'Программирование',
                            'design' => 'Дизайн',
                            'business' => 'Бизнес',
                            'marketing' => 'Маркетинг',
                            'languages' => 'Языки',
                            'health' => 'Здоровье',
                            'art' => 'Искусство',
                            'science' => 'Наука'
                        ])->required()->columnSpan(1),
                        Select::make('level')->label('Уровень')->options([
                            'beginner' => 'Для начинающих',
                            'intermediate' => 'Средний',
                            'advanced' => 'Продвинутый',
                            'expert' => 'Экспертный'
                        ])->columnSpan(1),
                    ]),

                Section::make('Описание')
                    ->collapsed()
                    ->schema([
                        Textarea::make('short_description')->label('Краткое описание')->maxLength(500)->rows(3),
                        RichEditor::make('full_description')->label('Полное описание')->maxLength(5000)->columnSpan('full'),
                    ]),

                Section::make('Инструктор')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('instructor_name')->label('Имя инструктора')->required()->columnSpan(1),
                        TextInput::make('instructor_email')->label('Email инструктора')->email()->columnSpan(1),
                        Textarea::make('instructor_bio')->label('О инструкторе')->maxLength(500)->rows(3)->columnSpan(2),
                        FileUpload::make('instructor_photo')->label('Фото инструктора')->image()->directory('education-instructors'),
                    ]),

                Section::make('Информация о курсе')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_hours')->label('Всего часов')->numeric()->columnSpan(1),
                        TextInput::make('total_lessons')->label('Уроков')->numeric()->columnSpan(1),
                        TextInput::make('total_modules')->label('Модулей')->numeric()->columnSpan(1),
                        TextInput::make('total_quizzes')->label('Тестов')->numeric()->columnSpan(1),
                        TextInput::make('max_students')->label('Максимум учеников')->numeric()->columnSpan(1),
                        TextInput::make('current_enrolled')->label('Записано')->numeric()->columnSpan(1),
                        DatePicker::make('course_start_date')->label('Начало курса')->columnSpan(1),
                        DatePicker::make('course_end_date')->label('Конец курса')->columnSpan(1),
                    ]),

                Section::make('Формат обучения')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Select::make('format')->label('Формат')->options([
                            'self_paced' => 'В собственном темпе',
                            'live_sessions' => 'Живые сессии',
                            'hybrid' => 'Гибридный',
                            'on_demand' => 'По требованию'
                        ])->columnSpan(1),
                        Toggle::make('has_live_sessions')->label('Живые сессии')->columnSpan(1),
                        TextInput::make('live_sessions_per_week')->label('Сессий в неделю')->numeric()->columnSpan(1),
                        Toggle::make('has_recorded_content')->label('Записанный контент')->columnSpan(1),
                        Toggle::make('has_live_chat')->label('Live чат')->columnSpan(1),
                        Toggle::make('has_q_and_a')->label('Q&A сессии')->columnSpan(1),
                        Toggle::make('has_assignments')->label('Задания')->columnSpan(1),
                        Toggle::make('has_projects')->label('Проекты')->columnSpan(1),
                    ]),

                Section::make('Сертификация')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('offers_certificate')->label('Сертификат')->columnSpan(1),
                        TextInput::make('certificate_name')->label('Название сертификата')->columnSpan(1),
                        TextInput::make('passing_score_percent')->label('Проходной балл (%)')->numeric()->columnSpan(1),
                        Toggle::make('certificate_has_qr')->label('QR-код в сертификате')->columnSpan(1),
                    ]),

                Section::make('Цены и доступ')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')->label('Цена курса (₽)')->numeric()->columnSpan(1),
                        TextInput::make('discount_price')->label('Цена со скидкой (₽)')->numeric()->columnSpan(1),
                        TextInput::make('discount_percent')->label('Скидка (%)')->numeric()->columnSpan(1),
                        Toggle::make('is_free')->label('Бесплатный')->columnSpan(1),
                        TextInput::make('access_duration_days')->label('Доступ (дней)')->numeric()->columnSpan(1),
                        Toggle::make('lifetime_access')->label('Пожизненный доступ')->columnSpan(1),
                        Toggle::make('money_back_guarantee')->label('Гарантия возврата денег')->columnSpan(1),
                        TextInput::make('guarantee_days')->label('Гарантия (дней)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Требования и результаты')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Textarea::make('prerequisites')->label('Требования')->maxLength(1000)->rows(3)->columnSpan(2),
                        RichEditor::make('learning_outcomes')->label('Результаты обучения')->maxLength(2000)->columnSpan('full'),
                        Textarea::make('skills_gained')->label('Навыки')->maxLength(1000)->rows(3)->columnSpan(2),
                    ]),

                Section::make('Программа обучения')
                    ->collapsed()
                    ->schema([
                        Repeater::make('modules')->label('Модули')
                            ->schema([
                                TextInput::make('module_title')->label('Название')->required(),
                                TextInput::make('module_duration_hours')->label('Длительность (часов)')->numeric(),
                                TextInput::make('lessons_count')->label('Уроков')->numeric(),
                                Textarea::make('topics')->label('Темы')->maxLength(500)->rows(2),
                            ])->columnSpan('full'),
                    ]),

                Section::make('Рейтинг и отзывы')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric(decimals: 1)->max(5)->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->columnSpan(1),
                        TextInput::make('completion_rate')->label('Завершают курс (%)')->numeric()->columnSpan(1),
                        TextInput::make('recommendation_rate')->label('Рекомендуют (%)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Поддержка')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_email_support')->label('Email поддержка')->columnSpan(1),
                        Toggle::make('has_chat_support')->label('Chat поддержка')->columnSpan(1),
                        Toggle::make('has_phone_support')->label('Телефонная поддержка')->columnSpan(1),
                        TextInput::make('support_response_hours')->label('Ответ за (часов)')->numeric()->columnSpan(1),
                    ]),

                Section::make('Языки')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TagsInput::make('languages')->label('Языки обучения')->columnSpan(2),
                        Toggle::make('has_subtitles')->label('Субтитры')->columnSpan(1),
                        TextInput::make('subtitles_languages')->label('Языки субтитров')->columnSpan(1),
                    ]),

                Section::make('Медиа')
                    ->collapsed()
                    ->schema([
                        FileUpload::make('course_preview_image')->label('Превью курса')->image()->directory('education-preview'),
                        FileUpload::make('course_banner')->label('Баннер')->image()->directory('education-banner'),
                        FileUpload::make('promotional_video')->label('Промо-видео')->acceptedFileTypes(['video/*'])->directory('education-videos'),
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
                ImageColumn::make('course_preview_image')->label('Фото')->size(40),
                TextColumn::make('title')->label('Название')->searchable()->sortable()->weight('bold')->limit(30),
                TextColumn::make('category')->label('Категория')->badge()->color('info'),
                TextColumn::make('level')->label('Уровень')->badge()->color('secondary'),
                TextColumn::make('rating')->label('Рейтинг')->numeric(decimals: 1)->badge()->color('warning')->sortable(),
                TextColumn::make('current_enrolled')->label('Записано')->numeric(),
                TextColumn::make('total_hours')->label('Часов')->numeric()->badge()->color('success'),
                BadgeColumn::make('offers_certificate')->label('Сертификат')->colors(['success' => true, 'gray' => false]),
                BadgeColumn::make('has_live_sessions')->label('Live')->colors(['info' => true, 'gray' => false]),
                BadgeColumn::make('is_featured')->label('Избранное')->colors(['warning' => true, 'gray' => false]),
                TextColumn::make('course_code')->label('Код')->toggleable(isToggledHiddenByDefault: true),
            ])->filters([
                SelectFilter::make('category')->options([
                    'programming' => 'Программирование',
                    'design' => 'Дизайн',
                    'business' => 'Бизнес',
                    'marketing' => 'Маркетинг',
                ]),
                SelectFilter::make('level')->options([
                    'beginner' => 'Для начинающих',
                    'intermediate' => 'Средний',
                    'advanced' => 'Продвинутый',
                ]),
                Filter::make('certificate')->query(fn (Builder $q) => $q->where('offers_certificate', true))->label('С сертификатом'),
                Filter::make('free')->query(fn (Builder $q) => $q->where('is_free', true))->label('Бесплатные'),
            ])->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('rating', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListEducation::route('/'),
                'create' => Pages\CreateEducation::route('/create'),
                'edit' => Pages\EditEducation::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()->id);
        }
}

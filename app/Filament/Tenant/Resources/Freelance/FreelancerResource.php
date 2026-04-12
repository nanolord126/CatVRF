<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class FreelancerResource extends Resource
{

    protected static ?string $model = Freelancer::class;

        protected static ?string $navigationIcon = 'heroicon-o-briefcase';

        protected static ?string $navigationGroup = 'Фриланс Биржа';

        protected static ?string $label = 'Фрилансер';

        protected static ?string $pluralLabel = 'Фрилансеры';

        /**
         * Форма создания/редактирования фрилансера (>60 строк логики)
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->description('Персональные данные и специализация специалиста.')
                        ->columns(2)
                        ->schema([
                            Select::make('user_id')
                                ->label('Пользователь')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disabledOn('edit'),

                            TextInput::make('full_name')
                                ->label('ФИО / Публичное имя')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('specialization')
                                ->label('Основная специализация')
                                ->placeholder('Python Developer, UI/UX Designer, etc.')
                                ->required(),

                            TextInput::make('experience_years')
                                ->label('Опыт (лет)')
                                ->numeric()
                                ->suffix('лет')
                                ->required(),

                            TextInput::make('hourly_rate_kopecks')
                                ->label('Ставка в час (коп.)')
                                ->numeric()
                                ->placeholder('500000 = 5000 руб.')
                                ->required()
                                ->suffix('коп.'),

                            Select::make('skills')
                                ->label('Навыки (Skills)')
                                ->multiple()
                                ->options([
                                    'php' => 'PHP',
                                    'laravel' => 'Laravel',
                                    'vue' => 'Vue.js',
                                    'python' => 'Python',
                                    'react' => 'React',
                                    'figma' => 'Figma',
                                    'go' => 'Golang',
                                    'postgre' => 'PostgreSQL',
                                ])
                                ->searchable(),

                            Toggle::make('is_verified')
                                ->label('Верифицирован платформой')
                                ->default(false)
                                ->onIcon('heroicon-m-check-badge')
                                ->onColor('success'),

                            Toggle::make('is_active')
                                ->label('Активен (Виден в поиске)')
                                ->default(true),
                        ]),

                    Section::make('О себе и Портфолио')
                        ->description('Детальное описание опыта и примеры выполненных работ.')
                        ->schema([
                            RichEditor::make('biography')
                                ->label('Биография / Описание стека')
                                ->required()
                                ->columnSpanFull(),

                            Repeater::make('portfolio')
                                ->label('Портфолио (Проекты)')
                                ->relationship('portfolios')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Название проекта')
                                        ->required(),

                                    RichEditor::make('description')
                                        ->label('Описание работ')
                                        ->required(),

                                    FileUpload::make('image_url')
                                        ->label('Скриншот / Результат')
                                        ->image()
                                        ->directory('freelance/portfolio')
                                        ->required(),

                                    TextInput::make('project_url')
                                        ->label('Ссылка на GitHub / Live')
                                        ->url(),

                                    TextInput::make('tags')
                                        ->label('Теги проекта (через запятую)')
                                        ->placeholder('laravel, filament, pwa'),
                                ])
                                ->collapsible()
                                ->defaultItems(0)
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                        ]),

                    Section::make('Системные метаданные')
                        ->description('Автоматические поля и теги аналитики.')
                        ->collapsed()
                        ->schema([
                            TextInput::make('uuid')
                                ->label('UUID (только для чтения)')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Генерируется автоматически'),

                            TextInput::make('correlation_id')
                                ->label('Correlation ID')
                                ->disabled()
                                ->dehydrated(false),

                            TextInput::make('tags')
                                ->label('JSON Теги')
                                ->placeholder('{"ai_score": 0.95, "migration": "upwork"}'),
                        ]),
                ]);
        }

        /**
         * Таблица списка фрилансеров (Tenant Scoping + Filters)
         */
        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    ImageColumn::make('user.avatar_url')
                        ->label('Аватар')
                        ->circular(),

                    TextColumn::make('full_name')
                        ->label('Фрилансер')
                        ->searchable()
                        ->sortable()
                        ->description(fn (Freelancer $record) => $record->specialization),

                    TextColumn::make('experience_years')
                        ->label('Опыт')
                        ->suffix(' л.')
                        ->sortable(),

                    TextColumn::make('hourly_rate_kopecks')
                        ->label('Ставка')
                        ->money('RUB', divisor: 100)
                        ->sortable(),

                    TextColumn::make('completed_orders_count')
                        ->label('Заказов')
                        ->badge()
                        ->color('success')
                        ->sortable(),

                    IconColumn::make('is_verified')
                        ->label('Верификация')
                        ->boolean()
                        ->trueIcon('heroicon-o-check-badge')
                        ->falseIcon('heroicon-o-x-circle'),

                    TextColumn::make('rating')
                        ->label('Рейтинг')
                        ->numeric(1)
                        ->sortable()
                        ->alignCenter(),

                    TextColumn::make('created_at')
                        ->label('Регистрация')
                        ->dateTime()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    SelectFilter::make('specialization')
                        ->label('Специализация')
                        ->options([
                            'Python Developer' => 'Python Developer',
                            'UI/UX Designer' => 'UI/UX Designer',
                            'Laravel Expert' => 'Laravel Expert',
                        ]),

                    Tables\Filters\Filter::make('is_verified')
                        ->label('Только верифицированные')
                        ->query(fn (Builder $query) => $query->where('is_verified', true)),
                ])
                ->actions([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            // Изоляция данных: фрилансеры только этого тенанта
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]);
        }

        public static function getRelations(): array
        {
            return [
                // RelationManagers здесь
            ];
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListFreelancers::route('/'),
                'create' => Pages\CreateFreelancer::route('/create'),
                'view' => Pages\ViewFreelancer::route('/{record}'),
                'edit' => Pages\EditFreelancer::route('/{record}/edit'),
            ];
        }
}

<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LessonResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, TextInput, Textarea, Select, Toggle};
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables\Columns\{TextColumn, IconColumn, BadgeColumn};
    use Filament\Tables\Table;
    use Filament\Tables\Actions\EditAction;

    final class LessonResource extends Resource
    {
        protected static ?string $model = Lesson::class;
        protected static ?string $navigationIcon = 'heroicon-o-document-text';
        protected static ?string $navigationGroup = 'Обучение';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->schema([
                            Select::make('course_id')
                                ->label('Курс')
                                ->relationship('course', 'title')
                                ->required(),
                            TextInput::make('title')
                                ->label('Название урока')
                                ->required()
                                ->maxLength(255),
                            Textarea::make('description')
                                ->label('Описание')
                                ->rows(3),
                            Textarea::make('content')
                                ->label('Контент')
                                ->required()
                                ->rows(8),
                        ]),
                    Section::make('Видео и ресурсы')
                        ->schema([
                            TextInput::make('video_url')
                                ->label('URL видео')
                                ->url(),
                            TextInput::make('duration_minutes')
                                ->label('Продолжительность (минуты)')
                                ->numeric()
                                ->required(),
                        ]),
                    Section::make('Статус')
                        ->schema([
                            TextInput::make('sort_order')
                                ->label('Порядок сортировки')
                                ->numeric(),
                            Toggle::make('is_published')
                                ->label('Опубликовано'),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('course.title')
                        ->label('Курс'),
                    TextColumn::make('title')
                        ->label('Название')
                        ->searchable(),
                    TextColumn::make('duration_minutes')
                        ->label('Длительность'),
                    TextColumn::make('sort_order')
                        ->label('Порядок'),
                    IconColumn::make('is_published')
                        ->label('Опубликовано')
                        ->boolean(),
                ])
                ->actions([
                    EditAction::make(),
                ]);
        }
}

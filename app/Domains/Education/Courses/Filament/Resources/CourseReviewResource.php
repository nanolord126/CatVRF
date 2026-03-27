<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Filament\Resources;

use App\Domains\Education\Courses\Models\CourseReview;
use Filament\Forms\Components\{Section, Select, TextInput, Textarea, Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{TextColumn, IconColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;

final class CourseReviewResource extends Resource
{
    protected static ?string $model = CourseReview::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Обучение';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Отзыв')
                    ->schema([
                        Select::make('course_id')
                            ->label('Курс')
                            ->relationship('course', 'title')
                            ->required()
                            ->disabled(),
                        Select::make('student_id')
                            ->label('Студент')
                            ->relationship('student', 'name')
                            ->required()
                            ->disabled(),
                        TextInput::make('rating')
                            ->label('Рейтинг (1-5)')
                            ->numeric()
                            ->required(),
                        TextInput::make('title')
                            ->label('Заголовок')
                            ->required(),
                        Textarea::make('content')
                            ->label('Текст отзыва')
                            ->required()
                            ->rows(5),
                    ]),
                Section::make('Статус')
                    ->schema([
                        Toggle::make('verified_purchase')
                            ->label('Проверенная покупка'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.title')
                    ->label('Курс'),
                TextColumn::make('student.name')
                    ->label('Студент'),
                BadgeColumn::make('rating')
                    ->label('Рейтинг')
                    ->colors([
                        'danger' => 1,
                        'warning' => 2,
                        'info' => 3,
                        'success' => 4,
                    ]),
                TextColumn::make('title')
                    ->label('Заголовок'),
                IconColumn::make('verified_purchase')
                    ->label('Проверено')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label('Опубликовано')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('rating'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}

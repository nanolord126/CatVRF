<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Courses;

use App\Domains\Courses\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Education';
    protected static ?string $navigationLabel = 'Courses';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('instructor_id')
                ->relationship('instructor', 'name')
                ->required(),
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->required()
                ->maxLength(65535),
            Forms\Components\TextInput::make('price')
                ->required()
                ->numeric()
                ->prefix('₽'),
            Forms\Components\TextInput::make('duration_hours')
                ->required()
                ->numeric(),
            Forms\Components\Select::make('level')
                ->options([
                    'beginner' => 'Beginner',
                    'intermediate' => 'Intermediate',
                    'advanced' => 'Advanced',
                ])
                ->required(),
            Forms\Components\FileUpload::make('thumbnail_url')
                ->image(),
            Forms\Components\Toggle::make('is_published')
                ->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url'),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('instructor.name'),
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB', divideBy: 100),
                Tables\Columns\BadgeColumn::make('level'),
                Tables\Columns\ToggleColumn::make('is_published'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}

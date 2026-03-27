<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning;

use App\Domains\Education\LanguageLearning\Models\LanguageCourse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\Education\LanguageLearning\Enums\LanguageLevel;
use Illuminate\Support\Str;

/**
 * Ресурс Курса Языка для Filament по канону 2026.
 * Форма > 60 строк, Таблица с агрегатами.
 */
final class LanguageCourseResource extends Resource
{
    protected static ?string $model = LanguageCourse::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Language Learning';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Identity')
                    ->schema([
                        Forms\Components\Select::make('school_id')
                            ->relationship('school', 'name')
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('teacher_id')
                            ->relationship('teacher', 'full_name')
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\RichEditor::make('description')
                            ->columnSpan(2),
                    ])->columns(2),

                Forms\Components\Section::make('Level & Categorization')
                    ->schema([
                        Forms\Components\TextInput::make('language')
                            ->required()
                            ->placeholder('e.g., German, Spanish'),

                        Forms\Components\Select::make('level_from')
                            ->options([
                                'A0' => 'A0 (Beginner)',
                                'A1' => 'A1',
                                'A2' => 'A2',
                                'B1' => 'B1',
                                'B2' => 'B2',
                                'C1' => 'C1',
                                'C2' => 'C2 (Native)',
                            ])
                            ->required(),

                        Forms\Components\Select::make('level_to')
                            ->options([
                                'A1' => 'A1',
                                'A2' => 'A2',
                                'B1' => 'B1',
                                'B2' => 'B2',
                                'C1' => 'C1',
                                'C2' => 'C2 (Native)',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('category')
                            ->placeholder('General, Business, Travel, Exam Preparation'),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('price_total')
                            ->numeric()
                            ->prefix('RUB')
                            ->required()
                            ->helperText('Price for the whole course in cents'),

                        Forms\Components\TextInput::make('price_per_lesson')
                            ->numeric()
                            ->prefix('RUB')
                            ->required(),

                        Forms\Components\TextInput::make('max_students')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->label('Group Size Limit'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Publish to Marketplace')
                            ->default(true)
                            ->onColor('success'),
                    ])->columns(2),

                Forms\Components\Section::make('Configuration & Audit')
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Search Tags'),

                        Forms\Components\TextInput::make('correlation_id')
                            ->default(Str::uuid())
                            ->disabled()
                            ->label('Trace ID'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('language')
                    ->badge(),

                Tables\Columns\TextColumn::make('level_from')
                    ->label('Level Range')
                    ->formatStateUsing(fn ($record) => "{$record->level_from} → {$record->level_to}"),

                Tables\Columns\TextColumn::make('teacher.full_name')
                    ->label('Instructor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price_total')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Total Price'),

                Tables\Columns\TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Students')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('language'),
                Tables\Filters\SelectFilter::make('level_from'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLanguageCourses::route('/'),
            'create' => Pages\CreateLanguageCourse::route('/create'),
            'edit' => Pages\EditLanguageCourse::route('/{record}/edit'),
        ];
    }
}

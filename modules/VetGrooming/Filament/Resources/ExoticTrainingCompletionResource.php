<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\ExoticTrainingCompletionModel;
use Modules\VetGrooming\Infrastructure\Models\ExoticTrainingCourseModel;

class ExoticTrainingCompletionResource extends Resource
{
    protected static ?string $model = ExoticTrainingCompletionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Exotic Grooming';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Exotic Training Completions';

    public static function getLabel(): string
    {
        return 'Exotic Training Completion';
    }

    public static function getPluralLabel(): string
    {
        return 'Exotic Training Completions';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Groomer & Course Information')
                    ->schema([
                        Forms\Components\Select::make('master_id')
                            ->relationship('master', 'full_name')
                            ->searchable()
                            ->required()
                            ->label('Groomer'),
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title')
                            ->searchable()
                            ->required()
                            ->label('Exotic Training Course')
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, $state) => 
                                $set('passing_score', ExoticTrainingCourseModel::find($state)?->passing_score ?? 80)
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Completion Results')
                    ->schema([
                        Forms\Components\DatePicker::make('completed_at')
                            ->required()
                            ->label('Completion Date')
                            ->default(now()),
                        Forms\Components\TextInput::make('score')
                            ->numeric()
                            ->suffix('%')
                            ->maxValue(100)
                            ->minValue(0)
                            ->required()
                            ->label('Test Score')
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                $passingScore = $get('passing_score') ?? 80;
                                $set('passed', $state >= $passingScore);
                            }),
                        Forms\Components\Toggle::make('passed')
                            ->label('Passed')
                            ->disabled()
                            ->inline(false),
                        Forms\Components\TextInput::make('passing_score')
                            ->numeric()
                            ->suffix('%')
                            ->disabled()
                            ->label('Required Score')
                            ->default(80),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Practical Exam')
                    ->schema([
                        Forms\Components\TextInput::make('practical_exam_video')
                            ->url()
                            ->label('Practical Exam Video URL')
                            ->helperText('YouTube or video platform link showing practical skills'),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->label('Instructor Notes'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Certificate')
                    ->schema([
                        Forms\Components\FileUpload::make('certificate_file')
                            ->label('Certificate Document')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->directory('exotic-certificates')
                            ->maxSize(10240),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('master.full_name')
                    ->label('Groomer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->suffix('%')
                    ->numeric()
                    ->color(fn (string $state): string => $state >= 80 ? 'success' : 'danger'),
                Tables\Columns\IconColumn::make('passed')
                    ->label('Passed')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('course.target_group')
                    ->label('Target Group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'group_a' => 'danger',
                        'group_b' => 'warning',
                        'group_c' => 'success',
                        'all' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('passed')
                    ->options([
                        '1' => 'Passed',
                        '0' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('course.target_group')
                    ->options([
                        'group_a' => 'Group A (High Risk)',
                        'group_b' => 'Group B (Medium Risk)',
                        'group_c' => 'Group C (Basic)',
                        'all' => 'All Groups',
                    ])
                    ->label('Target Group'),
                Tables\Filters\Filter::make('with_practical_video')
                    ->query(fn ($query) => $query->whereNotNull('practical_exam_video'))
                    ->label('Has Practical Video'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('completed_at', 'desc');
    }
}

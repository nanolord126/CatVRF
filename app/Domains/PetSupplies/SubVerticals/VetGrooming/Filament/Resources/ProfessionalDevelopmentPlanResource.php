<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\ProfessionalDevelopmentPlanModel;

class ProfessionalDevelopmentPlanResource extends Resource
{
    protected static ?string $model = ProfessionalDevelopmentPlanModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Professional Development';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Information')
                    ->schema([
                        Forms\Components\Select::make('master_id')
                            ->relationship('master', 'full_name')
                            ->searchable()
                            ->required()
                            ->label('Master'),
                        Forms\Components\Select::make('profession_type')
                            ->options([
                                'vet' => 'Veterinarian',
                                'groomer' => 'Groomer',
                            ])
                            ->required()
                            ->label('Profession Type'),
                        Forms\Components\Select::make('current_level')
                            ->options([
                                'junior' => 'Junior',
                                'intermediate' => 'Intermediate',
                                'senior' => 'Senior',
                                'expert' => 'Expert',
                                'master' => 'Master',
                            ])
                            ->required()
                            ->label('Current Level'),
                        Forms\Components\Select::make('target_level')
                            ->options([
                                'junior' => 'Junior',
                                'intermediate' => 'Intermediate',
                                'senior' => 'Senior',
                                'expert' => 'Expert',
                                'master' => 'Master',
                            ])
                            ->required()
                            ->label('Target Level'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'paused' => 'Paused',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->label('Status'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\DatePicker::make('plan_start_date')
                            ->required()
                            ->label('Plan Start Date'),
                        Forms\Components\DatePicker::make('plan_end_date')
                            ->required()
                            ->label('Plan End Date'),
                        Forms\Components\DatePicker::make('next_review_date')
                            ->required()
                            ->label('Next Review Date'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Progress')
                    ->schema([
                        Forms\Components\TextInput::make('total_courses_required')
                            ->numeric()
                            ->label('Total Courses Required'),
                        Forms\Components\TextInput::make('courses_completed')
                            ->numeric()
                            ->label('Courses Completed'),
                        Forms\Components\TextInput::make('completion_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->label('Completion Percentage'),
                        Forms\Components\TextInput::make('effectiveness_score')
                            ->numeric()
                            ->label('Effectiveness Score'),
                        Forms\Components\TextInput::make('development_score')
                            ->numeric()
                            ->label('Development Score'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Goals & Notes')
                    ->schema([
                        Forms\Components\Textarea::make('career_goals')
                            ->rows(3)
                            ->label('Career Goals'),
                        Forms\Components\Textarea::make('skill_gaps')
                            ->rows(3)
                            ->label('Skill Gaps'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('master.full_name')
                    ->label('Master')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('profession_type')
                    ->label('Profession')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vet' => 'primary',
                        'groomer' => 'success',
                    }),
                Tables\Columns\TextColumn::make('current_level')
                    ->label('Current Level')
                    ->badge(),
                Tables\Columns\TextColumn::make('target_level')
                    ->label('Target Level')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'completed' => 'primary',
                        'paused' => 'warning',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('completion_percentage')
                    ->label('Completion')
                    ->suffix('%')
                    ->progressBar(),
                Tables\Columns\TextColumn::make('development_score')
                    ->label('Dev Score')
                    ->numeric(),
                Tables\Columns\TextColumn::make('next_review_date')
                    ->label('Next Review')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('profession_type')
                    ->options([
                        'vet' => 'Veterinarian',
                        'groomer' => 'Groomer',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'paused' => 'Paused',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

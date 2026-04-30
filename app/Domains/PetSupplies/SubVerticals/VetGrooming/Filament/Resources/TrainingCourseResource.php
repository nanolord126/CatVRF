<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\TrainingCourseModel;

class TrainingCourseResource extends Resource
{
    protected static ?string $model = TrainingCourseModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Professional Development';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Course Title'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->label('Description'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'internal' => 'Internal (CatCRM)',
                                'external' => 'External',
                            ])
                            ->required()
                            ->label('Category'),
                        Forms\Components\Select::make('profession_type')
                            ->options([
                                'vet' => 'Veterinarian',
                                'groomer' => 'Groomer',
                                'both' => 'Both',
                            ])
                            ->required()
                            ->label('Profession Type'),
                        Forms\Components\TextInput::make('specialization')
                            ->label('Specialization'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Course Details')
                    ->schema([
                        Forms\Components\TextInput::make('duration_hours')
                            ->numeric()
                            ->label('Duration (Hours)'),
                        Forms\Components\Select::make('required_for_level')
                            ->options([
                                'junior' => 'Junior',
                                'intermediate' => 'Intermediate',
                                'senior' => 'Senior',
                                'expert' => 'Expert',
                                'master' => 'Master',
                                'all' => 'All Levels',
                            ])
                            ->label('Required For Level'),
                        Forms\Components\Toggle::make('is_mandatory')
                            ->label('Is Mandatory Course'),
                        Forms\Components\Toggle::make('certificate_issued')
                            ->label('Certificate Issued'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Mandatory For')
                    ->schema([
                        Forms\Components\TagsInput::make('mandatory_for_specializations')
                            ->label('Mandatory For Specializations'),
                        Forms\Components\TagsInput::make('mandatory_for_breeds')
                            ->label('Mandatory For Breeds'),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get): bool => $get('is_mandatory')),

                Forms\Components\Section::make('Certificate Template')
                    ->schema([
                        Forms\Components\TextInput::make('certificate_template')
                            ->label('Certificate Template Path'),
                    ])
                    ->visible(fn (Forms\Get $get): bool => $get('certificate_issued')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'internal' => 'primary',
                        'external' => 'success',
                    }),
                Tables\Columns\TextColumn::make('profession_type')
                    ->label('Profession')
                    ->badge(),
                Tables\Columns\TextColumn::make('specialization')
                    ->label('Specialization')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration_hours')
                    ->label('Duration (h)')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_mandatory')
                    ->label('Mandatory')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('required_for_level')
                    ->label('Required For')
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'internal' => 'Internal',
                        'external' => 'External',
                    ]),
                Tables\Filters\SelectFilter::make('profession_type')
                    ->options([
                        'vet' => 'Veterinarian',
                        'groomer' => 'Groomer',
                        'both' => 'Both',
                    ]),
                Tables\Filters\TernaryFilter::make('is_mandatory')
                    ->label('Mandatory Only'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
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

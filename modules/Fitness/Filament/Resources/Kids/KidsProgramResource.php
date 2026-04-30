<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Kids\KidsProgramModel;

final class KidsProgramResource extends Resource
{
    protected static ?string $model = KidsProgramModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Fitness - Kids';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Program Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Program Name'),
                        
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(65535)
                            ->label('Description')
                            ->rows(3),
                        
                        Forms\Components\Select::make('target_age_group')
                            ->options([
                                'preschool' => 'Preschool (3-5 years)',
                                'school_6_8' => 'School (6-8 years)',
                                'school_9_12' => 'School (9-12 years)',
                                'teens' => 'Teens (13-17 years)',
                            ])
                            ->required()
                            ->label('Target Age Group'),
                    ]),
                
                Forms\Components\Section::make('Schedule & Duration')
                    ->schema([
                        Forms\Components\TextInput::make('duration_weeks')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->suffix('weeks')
                            ->label('Duration'),
                        
                        Forms\Components\TextInput::make('sessions_per_week')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->label('Sessions Per Week'),
                        
                        Forms\Components\TextInput::make('session_duration_minutes')
                            ->numeric()
                            ->required()
                            ->minValue(15)
                            ->suffix('min')
                            ->label('Session Duration'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('₽')
                            ->required()
                            ->label('Price'),
                        
                        Forms\Components\TextInput::make('max_participants')
                            ->numeric()
                            ->minValue(1)
                            ->label('Max Participants')
                            ->helperText('Leave empty for unlimited'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Program Details')
                    ->schema([
                        Forms\Components\KeyValue::make('activities')
                            ->label('Activities')
                            ->keyLabel('Activity Name')
                            ->valueLabel('Description')
                            ->addable()
                            ->editable()
                            ->deletable(),
                        
                        Forms\Components\KeyValue::make('safety_guidelines')
                            ->label('Safety Guidelines')
                            ->keyLabel('Guideline')
                            ->valueLabel('Details')
                            ->addable()
                            ->editable()
                            ->deletable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Program Name'),
                
                Tables\Columns\TextColumn::make('target_age_group')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'preschool' => 'warning',
                        'school_6_8' => 'info',
                        'school_9_12' => 'success',
                        'teens' => 'primary',
                    })
                    ->label('Age Group'),
                
                Tables\Columns\TextColumn::make('duration_weeks')
                    ->numeric()
                    ->suffix(' weeks')
                    ->label('Duration'),
                
                Tables\Columns\TextColumn::make('sessions_per_week')
                    ->numeric()
                    ->label('Sessions/Week'),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->label('Price'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                
                Tables\Filters\SelectFilter::make('target_age_group')
                    ->options([
                        'preschool' => 'Preschool (3-5 years)',
                        'school_6_8' => 'School (6-8 years)',
                        'school_9_12' => 'School (9-12 years)',
                        'teens' => 'Teens (13-17 years)',
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Fitness\Filament\Resources\Kids\Pages\ListKidsPrograms::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Kids\Pages\CreateKidsProgram::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Kids\Pages\EditKidsProgram::route('/{record}/edit'),
        ];
    }
}

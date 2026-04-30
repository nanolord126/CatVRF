<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Senior\SeniorProgramModel;

final class SeniorProgramResource extends Resource
{
    protected static ?string $model = SeniorProgramModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Fitness - Senior (55+)';

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
                        
                        Forms\Components\TextInput::make('focus_area')
                            ->required()
                            ->maxLength(255)
                            ->label('Focus Area')
                            ->helperText('e.g., Balance, Strength, Mobility'),
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
                        Forms\Components\KeyValue::make('exercises')
                            ->label('Exercises')
                            ->keyLabel('Exercise Name')
                            ->valueLabel('Description')
                            ->addable()
                            ->editable()
                            ->deletable(),
                        
                        Forms\Components\KeyValue::make('safety_requirements')
                            ->label('Safety Requirements')
                            ->keyLabel('Requirement')
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
                
                Tables\Columns\TextColumn::make('focus_area')
                    ->searchable()
                    ->label('Focus Area'),
                
                Tables\Columns\TextColumn::make('duration_weeks')
                    ->numeric()
                    ->suffix(' weeks')
                    ->label('Duration'),
                
                Tables\Columns\TextColumn::make('sessions_per_week')
                    ->numeric()
                    ->label('Sessions/Week'),
                
                Tables\Columns\TextColumn::make('session_duration_minutes')
                    ->numeric()
                    ->suffix(' min')
                    ->label('Duration'),
                
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
                
                Tables\Filters\SelectFilter::make('focus_area')
                    ->options([
                        'balance' => 'Balance',
                        'strength' => 'Strength',
                        'mobility' => 'Mobility',
                        'cardio' => 'Cardio',
                        'flexibility' => 'Flexibility',
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
            'index' => \Modules\Fitness\Filament\Resources\Senior\Pages\ListSeniorPrograms::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Senior\Pages\CreateSeniorProgram::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Senior\Pages\EditSeniorProgram::route('/{record}/edit'),
        ];
    }
}

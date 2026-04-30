<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Senior;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Senior\SeniorHealthProfileModel;

final class SeniorHealthProfileResource extends Resource
{
    protected static ?string $model = SeniorHealthProfileModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-medical';

    protected static ?string $navigationGroup = 'Fitness - Senior (55+)';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Health Conditions')
                    ->schema([
                        Forms\Components\Toggle::make('has_heart_condition')
                            ->label('Heart Condition')
                            ->helperText('Requires medical clearance'),
                        
                        Forms\Components\Toggle::make('has_diabetes')
                            ->label('Diabetes')
                            ->helperText('Requires medical clearance'),
                        
                        Forms\Components\Toggle::make('has_joint_problems')
                            ->label('Joint Problems'),
                        
                        Forms\Components\Toggle::make('has_mobility_limitations')
                            ->label('Mobility Limitations'),
                        
                        Forms\Components\Toggle::make('has_balance_issues')
                            ->label('Balance Issues'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Medical Information')
                    ->schema([
                        Forms\Components\Textarea::make('medications')
                            ->label('Medications')
                            ->rows(3)
                            ->helperText('List current medications'),
                        
                        Forms\Components\Textarea::make('allergies')
                            ->label('Allergies')
                            ->rows(2),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Emergency Contact')
                    ->schema([
                        Forms\Components\TextInput::make('emergency_contact')
                            ->maxLength(255)
                            ->label('Contact Name'),
                        
                        Forms\Components\TextInput::make('emergency_phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Phone Number'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Fitness Level')
                    ->schema([
                        Forms\Components\Select::make('fitness_level')
                            ->options([
                                'beginner' => 'Beginner',
                                'intermediate' => 'Intermediate',
                                'advanced' => 'Advanced',
                            ])
                            ->default('beginner')
                            ->required()
                            ->label('Fitness Level'),
                    ]),
                
                Forms\Components\Section::make('Physical Limitations')
                    ->schema([
                        Forms\Components\Textarea::make('physical_limitations')
                            ->label('Limitations')
                            ->rows(3)
                            ->helperText('Describe any physical limitations'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.first_name')
                    ->searchable()
                    ->label('Client'),
                
                Tables\Columns\IconColumn::make('has_heart_condition')
                    ->boolean()
                    ->label('Heart'),
                
                Tables\Columns\IconColumn::make('has_diabetes')
                    ->boolean()
                    ->label('Diabetes'),
                
                Tables\Columns\IconColumn::make('has_balance_issues')
                    ->boolean()
                    ->label('Balance'),
                
                Tables\Columns\TextColumn::make('fitness_level')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'beginner' => 'gray',
                        'intermediate' => 'warning',
                        'advanced' => 'success',
                    })
                    ->label('Fitness Level'),
                
                Tables\Columns\TextColumn::make('emergency_contact')
                    ->searchable()
                    ->label('Emergency Contact'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_heart_condition')
                    ->label('Heart Condition'),
                
                Tables\Filters\TernaryFilter::make('has_diabetes')
                    ->label('Diabetes'),
                
                Tables\Filters\SelectFilter::make('fitness_level')
                    ->options([
                        'beginner' => 'Beginner',
                        'intermediate' => 'Intermediate',
                        'advanced' => 'Advanced',
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
            'index' => \Modules\Fitness\Filament\Resources\Senior\Pages\ListSeniorHealthProfiles::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Senior\Pages\CreateSeniorHealthProfile::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Senior\Pages\EditSeniorHealthProfile::route('/{record}/edit'),
        ];
    }
}

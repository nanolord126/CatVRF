<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Kids\KidsHealthProfileModel;

final class KidsHealthProfileResource extends Resource
{
    protected static ?string $model = KidsHealthProfileModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-medical';

    protected static ?string $navigationGroup = 'Fitness - Kids';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Child Information')
                    ->schema([
                        Forms\Components\Select::make('age_group')
                            ->options([
                                'preschool' => 'Preschool (3-5 years)',
                                'school_6_8' => 'School (6-8 years)',
                                'school_9_12' => 'School (9-12 years)',
                                'teens' => 'Teens (13-17 years)',
                            ])
                            ->required()
                            ->label('Age Group'),
                        
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Birth Date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Parent Information')
                    ->schema([
                        Forms\Components\TextInput::make('parent_name')
                            ->maxLength(255)
                            ->label('Parent Name'),
                        
                        Forms\Components\TextInput::make('parent_phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Parent Phone'),
                        
                        Forms\Components\TextInput::make('parent_email')
                            ->email()
                            ->maxLength(255)
                            ->label('Parent Email'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Health Conditions')
                    ->schema([
                        Forms\Components\Toggle::make('has_allergies')
                            ->label('Has Allergies')
                            ->live(),
                        
                        Forms\Components\Toggle::make('has_asthma')
                            ->label('Has Asthma')
                            ->helperText('Requires medical clearance'),
                        
                        Forms\Components\Toggle::make('has_heart_condition')
                            ->label('Has Heart Condition')
                            ->helperText('Requires medical clearance'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Medical Information')
                    ->schema([
                        Forms\Components\Textarea::make('allergies')
                            ->label('Allergies Details')
                            ->rows(2)
                            ->visible(fn ($get) => $get('has_allergies')),
                        
                        Forms\Components\Textarea::make('medications')
                            ->label('Medications')
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
                
                Tables\Columns\TextColumn::make('age_group')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'preschool' => 'warning',
                        'school_6_8' => 'info',
                        'school_9_12' => 'success',
                        'teens' => 'primary',
                    })
                    ->label('Age Group'),
                
                Tables\Columns\TextColumn::make('parent_name')
                    ->searchable()
                    ->label('Parent'),
                
                Tables\Columns\IconColumn::make('has_asthma')
                    ->boolean()
                    ->label('Asthma'),
                
                Tables\Columns\IconColumn::make('has_heart_condition')
                    ->boolean()
                    ->label('Heart Condition'),
                
                Tables\Columns\TextColumn::make('emergency_contact')
                    ->searchable()
                    ->label('Emergency Contact'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_asthma')
                    ->label('Asthma'),
                
                Tables\Filters\TernaryFilter::make('has_heart_condition')
                    ->label('Heart Condition'),
                
                Tables\Filters\SelectFilter::make('age_group')
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
            'index' => \Modules\Fitness\Filament\Resources\Kids\Pages\ListKidsHealthProfiles::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Kids\Pages\CreateKidsHealthProfile::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Kids\Pages\EditKidsHealthProfile::route('/{record}/edit'),
        ];
    }
}

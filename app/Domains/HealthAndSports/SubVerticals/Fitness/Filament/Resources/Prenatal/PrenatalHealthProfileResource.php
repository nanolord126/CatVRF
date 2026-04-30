<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Prenatal\PrenatalHealthProfileModel;

final class PrenatalHealthProfileResource extends Resource
{
    protected static ?string $model = PrenatalHealthProfileModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-medical';

    protected static ?string $navigationGroup = 'Fitness - Prenatal';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pregnancy Information')
                    ->schema([
                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->label('Due Date'),
                        
                        Forms\Components\Select::make('trimester')
                            ->options([
                                'first' => 'First Trimester',
                                'second' => 'Second Trimester',
                                'third' => 'Third Trimester',
                            ])
                            ->required()
                            ->label('Trimester'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Health Conditions')
                    ->schema([
                        Forms\Components\Toggle::make('has_high_risk_pregnancy')
                            ->label('High Risk Pregnancy')
                            ->helperText('Requires medical clearance'),
                        
                        Forms\Components\Toggle::make('has_preeclampsia_risk')
                            ->label('Preeclampsia Risk')
                            ->helperText('Requires medical clearance'),
                        
                        Forms\Components\Toggle::make('has_gestational_diabetes')
                            ->label('Gestational Diabetes'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Medical Information')
                    ->schema([
                        Forms\Components\Textarea::make('obstetrician_notes')
                            ->label('Obstetrician Notes')
                            ->rows(3),
                        
                        Forms\Components\Textarea::make('medications')
                            ->label('Medications')
                            ->rows(2),
                        
                        Forms\Components\Textarea::make('allergies')
                            ->label('Allergies')
                            ->rows(2),
                    ])
                    ->columns(3),
                
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
                
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->label('Due Date'),
                
                Tables\Columns\TextColumn::make('trimester')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'first' => 'warning',
                        'second' => 'info',
                        'third' => 'danger',
                    })
                    ->label('Trimester'),
                
                Tables\Columns\IconColumn::make('has_high_risk_pregnancy')
                    ->boolean()
                    ->label('High Risk'),
                
                Tables\Columns\IconColumn::make('has_preeclampsia_risk')
                    ->boolean()
                    ->label('Preeclampsia'),
                
                Tables\Columns\TextColumn::make('emergency_contact')
                    ->searchable()
                    ->label('Emergency Contact'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('has_high_risk_pregnancy')
                    ->label('High Risk'),
                
                Tables\Filters\TernaryFilter::make('has_preeclampsia_risk')
                    ->label('Preeclampsia Risk'),
                
                Tables\Filters\SelectFilter::make('trimester')
                    ->options([
                        'first' => 'First Trimester',
                        'second' => 'Second Trimester',
                        'third' => 'Third Trimester',
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
            'index' => \Modules\Fitness\Filament\Resources\Prenatal\Pages\ListPrenatalHealthProfiles::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Prenatal\Pages\CreatePrenatalHealthProfile::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Prenatal\Pages\EditPrenatalHealthProfile::route('/{record}/edit'),
        ];
    }
}

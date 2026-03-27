<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Medical\Models\MedicalRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: MEDICAL RECORD RESOURCE (EHR)
 */
final class MedicalRecordResource extends Resource
{
    protected static ?string $model = MedicalRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Medical Platform';
    protected static ?string $slug = 'medical-records';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Patient Engagement')
                ->schema([
                    Forms\Components\Select::make('patient_id')
                        ->relationship('patient', 'name')
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('doctor_id')
                        ->relationship('doctor', 'full_name')
                        ->required(),
                    Forms\Components\Select::make('appointment_id')
                        ->relationship('appointment', 'appointment_number'),
                ])->columns(3),

            Forms\Components\Section::make('Clinical Data (ICD-10)')
                ->schema([
                    Forms\Components\TextInput::make('diagnosis_code')
                        ->label('ICD-10 Code')
                        ->required()
                        ->placeholder('e.g. J06.9'),
                    Forms\Components\Textarea::make('complaints')
                        ->required(),
                    Forms\Components\RichEditor::make('treatment_plan')
                        ->required(),
                ]),

            Forms\Components\Section::make('Vital Signs & Observations')
                ->schema([
                    Forms\Components\KeyValue::make('clinical_data')
                        ->label('Objective Data (Pressure, Temp, etc.)'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.full_name')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('diagnosis_code')
                    ->label('ICD-10')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Visit Date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('doctor_id')
                    ->relationship('doctor', 'full_name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\MedicalRecordResource\Pages\ListMedicalRecords::route('/'),
            'create' => \App\Filament\Tenant\Resources\MedicalRecordResource\Pages\CreateMedicalRecord::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\MedicalRecordResource\Pages\EditMedicalRecord::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Tenant\Resources\HR\EmployeeResource\RelationManagers;

use App\Models\Animal;
use App\Models\MedicalCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PetsRelationManager extends RelationManager
{
    protected static string $relationship = 'animals';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Питомцы и их медкарты';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('species')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('breed')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('birth_date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Кличка'),
                Tables\Columns\TextColumn::make('species')
                    ->label('Вид'),
                Tables\Columns\TextColumn::make('medical_cards_count')
                    ->label('Визитов')
                    ->counts('medicalCards')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Медкарта')
                    ->modalHeading(fn (Animal $record) => "Медкарта питомца: {$record->name}")
                    ->modalContent(fn (Animal $record) => view('filament.tenant.resources.hr.employee.modals.pet-history', [
                        'animal' => $record,
                        'history' => MedicalCard::where('patient_type', 'ANIMAL')
                            ->where('patient_id', $record->id)
                            ->orderBy('created_at', 'desc')
                            ->get()
                    ])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}

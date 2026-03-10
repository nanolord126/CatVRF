<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\MedicalAppointmentResource\Pages;
use App\Models\Tenants\MedicalAppointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MedicalAppointmentResource extends Resource
{
    protected static ?string $model = MedicalAppointment::class;
    protected static ?string $navigationGroup = 'Marketplace';
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('entity_type')
                    ->options([
                        'HUMAN' => 'Clinic (Human)',
                        'ANIMAL' => 'Vet Clinic (Animal)',
                    ])->required(),
                Forms\Components\Select::make('doctor_id')->relationship('doctor', 'name')->required(),
                Forms\Components\TextInput::make('patient_name')->required(),
                Forms\Components\DateTimePicker::make('scheduled_at')->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])->default('scheduled'),
                Forms\Components\Textarea::make('notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('entity_type')
                    ->colors([
                        'primary' => 'HUMAN',
                        'success' => 'ANIMAL',
                    ]),
                Tables\Columns\TextColumn::make('patient_name')->searchable(),
                Tables\Columns\TextColumn::make('doctor.name')->sortable(),
                Tables\Columns\TextColumn::make('scheduled_at')->dateTime()->sortable(),
                Tables\Columns\BadgeColumn::make('status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('entity_type')
                    ->options([
                        'HUMAN' => 'Human',
                        'ANIMAL' => 'Animal',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedicalAppointments::route('/'),
            'create' => Pages\CreateMedicalAppointment::route('/create'),
            'edit' => Pages\EditMedicalAppointment::route('/{record}/edit'),
        ];
    }
}

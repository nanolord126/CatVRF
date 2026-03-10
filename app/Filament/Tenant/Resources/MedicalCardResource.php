<?php

namespace App\Filament\Tenant\Resources;

use App\Models\MedicalCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MedicalCardResource extends Resource
{
    protected static ?string $model = MedicalCard::class;
    protected static ?string $navigationGroup = 'Medical & Vet';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_type')
                    ->options([
                        'HUMAN' => 'Human Patient',
                        'ANIMAL' => 'Animal Patient',
                    ])
                    ->required()
                    ->live(),
                Forms\Components\Select::make('patient_id')
                    ->label('Patient')
                    ->relationship(
                        name: 'patient',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Forms\Get $get, $query) => 
                            $get('patient_type') === 'HUMAN' 
                                ? $query->from('users') 
                                : $query->from('animals')
                    )
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('doctor_id')
                    ->relationship('doctor', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Textarea::make('symptoms')->required(),
                Forms\Components\Textarea::make('diagnosis')->required(),
                Forms\Components\Textarea::make('prescription')->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Open Treatment',
                        'closed' => 'Closed Account',
                    ])
                    ->required(),
                Forms\Components\Hidden::make('correlation_id')->default(fn () => (string) Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('patient_type')
                    ->colors([
                        'primary' => 'HUMAN',
                        'success' => 'ANIMAL',
                    ]),
                Tables\Columns\TextColumn::make('diagnosis')->limit(50),
                Tables\Columns\TextColumn::make('doctor.name')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\BadgeColumn::make('status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('patient_type'),
                Tables\Filters\SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => MedicalCardResource\Pages\ListMedicalCards::route('/'),
            'create' => MedicalCardResource\Pages\CreateMedicalCard::route('/create'),
            'edit' => MedicalCardResource\Pages\EditMedicalCard::route('/{record}/edit'),
        ];
    }
}

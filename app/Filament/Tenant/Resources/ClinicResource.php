<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Clinic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ClinicResource extends Resource
{
    protected static ?string $model = Clinic::class;
    protected static ?string $navigationGroup = 'Medical & Vet';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'human' => 'Human Clinic',
                        'vet' => 'Vet Clinic',
                    ])->required(),
                Forms\Components\TextInput::make('address'),
                Forms\Components\TextInput::make('phone')->tel(),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\TextInput::make('geo_lat')->numeric(),
                Forms\Components\TextInput::make('geo_lng')->numeric(),
                Forms\Components\Hidden::make('correlation_id')->default(fn () => (string) Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'human',
                        'success' => 'vet',
                    ]),
                Tables\Columns\TextColumn::make('address'),
                Tables\Columns\TextColumn::make('phone'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'human' => 'Human',
                        'vet' => 'Vet',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\DoctorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ClinicResource\Pages\ListClinics::route('/'),
            'create' => ClinicResource\Pages\CreateClinic::route('/create'),
            'edit' => ClinicResource\Pages\EditClinic::route('/{record}/edit'),
        ];
    }
}

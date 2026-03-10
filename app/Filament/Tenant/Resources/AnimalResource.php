<?php

namespace App\Filament\Tenant\Resources;

use App\Models\Animal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AnimalResource extends Resource
{
    protected static ?string $model = Animal::class;
    protected static ?string $navigationGroup = 'Medical & Vet';
    protected static ?string $navigationIcon = 'heroicon-o-bug-ant'; // Best for insects/nature, use paw if available
    protected static ?string $label = 'Pets (Animals)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('species')->required(), // cat, dog, etc.
                Forms\Components\TextInput::make('breed'),
                Forms\Components\DatePicker::make('birth_date'),
                Forms\Components\Select::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other',
                    ]),
                Forms\Components\TextInput::make('weight')->numeric()->suffix('kg'),
                Forms\Components\TextInput::make('chip_number'),
                Forms\Components\Textarea::make('notes'),
                Forms\Components\Hidden::make('correlation_id')->default(fn () => (string) Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('species')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->sortable(),
                Tables\Columns\TextColumn::make('breed'),
                Tables\Columns\TextColumn::make('birth_date')->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('species'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => AnimalResource\Pages\ListAnimals::route('/'),
            'create' => AnimalResource\Pages\CreateAnimal::route('/create'),
            'edit' => AnimalResource\Pages\EditAnimal::route('/{record}/edit'),
        ];
    }
}

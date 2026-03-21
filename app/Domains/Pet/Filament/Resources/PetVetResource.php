<?php declare(strict_types=1);

namespace App\Domains\Pet\Filament\Resources;

use App\Domains\Pet\Models\PetVet;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Table;

final class PetVetResource extends Resource
{
    protected static ?string $model = PetVet::class;

    protected static ?string $slug = 'pet-vets';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Pet Services';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('clinic_id')
                    ->relationship('clinic', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('full_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('specialization')
                    ->required()
                    ->maxLength(255),
                TextInput::make('experience_years')
                    ->numeric()
                    ->default(0),
                Textarea::make('bio')
                    ->maxLength(1000),
                TextInput::make('license_number')
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('consultation_price')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('clinic.name')
                    ->label('Clinic')
                    ->searchable(),
                TextColumn::make('specialization')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric(),
                BooleanColumn::make('is_active'),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }
}

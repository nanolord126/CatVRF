<?php declare(strict_types=1);

namespace App\Domains\Pet\Filament\Resources;

use Filament\Resources\Resource;

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
                    IconColumn::make('is_active')->boolean(),
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

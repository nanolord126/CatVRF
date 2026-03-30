<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PetResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = \App\Domains\Veterinary\Models\Pet::class;

        protected static ?string $navigationIcon = 'heroicon-o-heart';

        protected static ?string $navigationGroup = 'Veterinary Services';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Tabs::make('Pet Passport')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General Information')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Select::make('species')
                                            ->options([
                                                'dog' => 'Dog',
                                                'cat' => 'Cat',
                                                'bird' => 'Bird',
                                                'rabbit' => 'Rabbit',
                                                'other' => 'Other',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('breed')
                                            ->maxLength(255),
                                        Forms\Components\DatePicker::make('birth_date')
                                            ->required(),
                                        Forms\Components\Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('weight')
                                            ->numeric()
                                            ->suffix('kg'),
                                        Forms\Components\Toggle::make('is_neutered')
                                            ->label('Neutered/Spayed'),
                                        Forms\Components\TextInput::make('uuid')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->label('Internal ID (UUID)'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Official Passport')
                            ->icon('heroicon-o-book-open')
                            ->schema([
                                Forms\Components\Section::make('Chipping & Registration')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('chip_number')
                                                    ->label('Microchip Number')
                                                    ->placeholder('900...'),
                                                Forms\Components\DatePicker::make('chip_installed_at')
                                                    ->label('Date of Installation'),
                                                Forms\Components\TextInput::make('passport_number')
                                                    ->label('Passport Series/Number')
                                                    ->placeholder('RU-VET-000...'),
                                            ]),
                                    ]),
                                Forms\Components\Section::make('Pedigree')
                                    ->schema([
                                        Forms\Components\Repeater::make('pedigree')
                                            ->relationship('pedigree')
                                            ->schema([
                                                Forms\Components\TextInput::make('title')
                                                    ->label('Relation (Father/Mother)')
                                                    ->required(),
                                                Forms\Components\TextInput::make('parent_name')
                                                    ->required(),
                                                Forms\Components\TextInput::make('registration_number')
                                                    ->label('Reg Number'),
                                            ])
                                            ->columns(3)
                                            ->collapsible(),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Vaccination Log')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Repeater::make('vaccinations')
                                    ->relationship('vaccinations')
                                    ->schema([
                                        Forms\Components\TextInput::make('vaccine_name')
                                            ->required(),
                                        Forms\Components\TextInput::make('batch_number')
                                            ->label('Batch #'),
                                        Forms\Components\DatePicker::make('administered_at')
                                            ->required(),
                                        Forms\Components\DatePicker::make('valid_until')
                                            ->required(),
                                        Forms\Components\TextInput::make('vet_name')
                                            ->label('Signature (Vet Name)'),
                                    ])
                                    ->columns(5)
                                    ->collapsible(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Metrics History')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Forms\Components\Repeater::make('metrics')
                                    ->relationship('metrics')
                                    ->schema([
                                        Forms\Components\TextInput::make('metric_type')
                                            ->placeholder('Weight, Temp, HR')
                                            ->required(),
                                        Forms\Components\TextInput::make('value')
                                            ->required(),
                                        Forms\Components\TextInput::make('unit')
                                            ->required(),
                                        Forms\Components\DatePicker::make('measured_at')
                                            ->default(now())
                                            ->required(),
                                        Forms\Components\Textarea::make('notes')
                                            ->rows(1),
                                    ])
                                    ->columns(5)
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListPet::route('/'),
                'create' => Pages\\CreatePet::route('/create'),
                'edit' => Pages\\EditPet::route('/{record}/edit'),
                'view' => Pages\\ViewPet::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListPet::route('/'),
                'create' => Pages\\CreatePet::route('/create'),
                'edit' => Pages\\EditPet::route('/{record}/edit'),
                'view' => Pages\\ViewPet::route('/{record}'),
            ];
        }
}

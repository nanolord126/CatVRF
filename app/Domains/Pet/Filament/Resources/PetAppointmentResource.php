<?php declare(strict_types=1);

namespace App\Domains\Pet\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class PetAppointmentResource extends Resource
{

    protected static ?string $model = PetAppointment::class;

        protected static ?string $slug = 'pet-appointments';

        protected static ?string $navigationIcon = 'heroicon-o-calendar';

        protected static ?string $navigationGroup = 'Pet Services';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Select::make('clinic_id')
                        ->relationship('clinic', 'name')
                        ->required(),
                    Select::make('vet_id')
                        ->relationship('vet', 'full_name'),
                    Select::make('owner_id')
                        ->relationship('owner', 'name')
                        ->required(),
                    Select::make('service_id')
                        ->relationship('service', 'name')
                        ->required(),
                    TextInput::make('pet_name')
                        ->required(),
                    Select::make('pet_type')
                        ->options(['dog' => 'Dog', 'cat' => 'Cat', 'bird' => 'Bird', 'rabbit' => 'Rabbit', 'other' => 'Other'])
                        ->required(),
                    DateTimeInput::make('scheduled_at')
                        ->required(),
                    TextInput::make('price')
                        ->numeric()
                        ->required(),
                    Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->default('pending'),
                    Textarea::make('notes'),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('appointment_number')
                        ->searchable(),
                    TextColumn::make('pet_name')
                        ->searchable(),
                    TextColumn::make('clinic.name')
                        ->searchable(),
                    TextColumn::make('scheduled_at')
                        ->dateTime(),
                    TextColumn::make('status')->badge()
                        ->colors([
                            'pending' => 'gray',
                            'confirmed' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                        ]),
                    TextColumn::make('price')
                        ->money('RUB'),
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

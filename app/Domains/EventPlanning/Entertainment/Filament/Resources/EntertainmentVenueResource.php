<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EntertainmentVenueResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = EntertainmentVenue::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\RichEditor::make('description')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('address')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('venue_type')
                        ->options(['cinema' => 'Cinema', 'theater' => 'Theater', 'concert' => 'Concert Hall', 'arena' => 'Arena'])
                        ->required(),
                    Forms\Components\TextInput::make('seating_capacity')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('standard_ticket_price')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('premium_ticket_price')
                        ->numeric(),
                    Forms\Components\Toggle::make('is_verified')
                        ->default(false),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('seating_capacity'),
                    Tables\Columns\TextColumn::make('rating')
                        ->sortable(),
                    Tables\Columns\IconColumn::make('is_verified')
                        ->boolean(),
                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean(),
                ])
                ->filters([
                    Tables\Filters\TrashedFilter::make(),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentVenueResource\Pages\ListEntertainmentVenues::class,
                'create' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentVenueResource\Pages\CreateEntertainmentVenue::class,
                'edit' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainmentVenueResource\Pages\EditEntertainmentVenue::class,
            ];
        }
}

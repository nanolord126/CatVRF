<?php declare(strict_types=1);

namespace App\Domains\Hotels\Filament\Resources;

use App\Domains\Hotels\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

        protected static ?string $navigationIcon = 'heroicon-o-window';
        protected static ?string $navigationGroup = 'Hotels';
        protected static ?int $navigationSort = 2;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Select::make('hotel_id')
                        ->relationship('hotel', 'name')
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description'),
                    Forms\Components\TextInput::make('base_price_per_night')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('max_guests')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('available_count')
                        ->numeric()
                        ->required(),
                    Forms\Components\TagsInput::make('amenities'),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('hotel.name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('base_price_per_night')
                        ->numeric()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('max_guests')
                        ->numeric(),
                    Tables\Columns\TextColumn::make('available_count')
                        ->numeric(),
                    Tables\Columns\TextColumn::make('rating')
                        ->numeric(),
                ])
                ->filters([])
                ->actions([
                    Tables\Actions\EditAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListRoomTypes::route('/'),
                'create' => Pages\CreateRoomType::route('/create'),
                'edit' => Pages\EditRoomType::route('/{record}/edit'),
            ];
        }
}

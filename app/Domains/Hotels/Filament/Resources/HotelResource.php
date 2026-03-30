<?php declare(strict_types=1);

namespace App\Domains\Hotels\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HotelResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Hotel::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-library';
        protected static ?string $navigationGroup = 'Hotels';
        protected static ?int $navigationSort = 1;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('address')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\RangeSlider::make('star_rating')
                        ->min(1)
                        ->max(5)
                        ->required(),
                    Forms\Components\TextInput::make('total_rooms')
                        ->numeric()
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535),
                    Forms\Components\TagsInput::make('amenities'),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'closed' => 'Closed',
                            'maintenance' => 'Maintenance',
                        ])
                        ->default('active'),
                    Forms\Components\Toggle::make('is_verified')
                        ->default(false),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('address')
                        ->searchable(),
                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'success' => 'active',
                            'danger' => 'closed',
                            'warning' => 'maintenance',
                        ]),
                    Tables\Columns\TextColumn::make('rating')
                        ->numeric()
                        ->sortable(),
                    Tables\Columns\BooleanColumn::make('is_verified'),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status')
                        ->options([
                            'active' => 'Active',
                            'closed' => 'Closed',
                            'maintenance' => 'Maintenance',
                        ]),
                    Tables\Filters\TernaryFilter::make('is_verified'),
                ])
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
                'index' => Pages\ListHotels::route('/'),
                'create' => Pages\CreateHotel::route('/create'),
                'edit' => Pages\EditHotel::route('/{record}/edit'),
            ];
        }
}

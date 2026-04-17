<?php declare(strict_types=1);

namespace Modules\RealEstate\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\RealEstate\Models\PropertyBooking;
use Modules\RealEstate\Enums\BookingStatus;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;

final class PropertyBookingResource extends Resource
{
    protected static ?string $model = PropertyBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Real Estate';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('uuid')
                    ->disabled()
                    ->maxLength(255),

                Select::make('property_id')
                    ->relationship('property', 'title')
                    ->required()
                    ->searchable(),

                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),

                DateTimePicker::make('viewing_slot')
                    ->required(),

                TextInput::make('amount')
                    ->numeric()
                    ->prefix('₽')
                    ->required(),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'refunded' => 'Refunded',
                    ])
                    ->required(),

                TextInput::make('fraud_score')
                    ->numeric()
                    ->step(0.0001)
                    ->minValue(0)
                    ->maxValue(1),

                Toggle::make('is_b2b'),

                Toggle::make('face_id_verified'),

                Toggle::make('blockchain_verified'),

                Textarea::make('metadata')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),

                TextColumn::make('uuid')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('property.title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('viewing_slot')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('RUB')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (BookingStatus $state): string => match ($state) {
                        BookingStatus::PENDING => 'warning',
                        BookingStatus::CONFIRMED => 'info',
                        BookingStatus::COMPLETED => 'success',
                        BookingStatus::CANCELLED => 'danger',
                        BookingStatus::EXPIRED => 'gray',
                        BookingStatus::REFUNDED => 'gray',
                    }),

                BooleanColumn::make('is_b2b'),

                BooleanColumn::make('face_id_verified'),

                BooleanColumn::make('blockchain_verified'),

                TextColumn::make('fraud_score')
                    ->formatStateValue(fn ($state): string => number_format($state, 4)),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('is_b2b')
                    ->options([
                        '1' => 'B2B',
                        '0' => 'B2C',
                    ]),
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
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\RealEstate\Filament\Resources\PropertyBookingResource\Pages\ListPropertyBookings::route('/'),
            'create' => \Modules\RealEstate\Filament\Resources\PropertyBookingResource\Pages\CreatePropertyBooking::route('/create'),
            'view' => \Modules\RealEstate\Filament\Resources\PropertyBookingResource\Pages\ViewPropertyBooking::route('/{record}'),
            'edit' => \Modules\RealEstate\Filament\Resources\PropertyBookingResource\Pages\EditPropertyBooking::route('/{record}/edit'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\Hotels\Filament\Resources;

use App\Domains\Hotels\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\Hotels\Filament\Resources\BookingResource\Pages\CreateBooking;
use App\Domains\Hotels\Filament\Resources\BookingResource\Pages\EditBooking;
use App\Domains\Hotels\Filament\Resources\BookingResource\Pages\ListBookings;

final class BookingResource extends BaseOptimizedResource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationGroup = 'Hotels';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('hotel_id')
                    ->relationship('hotel', 'name')
                    ->required(),
                Forms\Components\Select::make('room_type_id')
                    ->relationship('roomType', 'name')
                    ->required(),
                Forms\Components\DatePicker::make('check_in_date')
                    ->required(),
                Forms\Components\DatePicker::make('check_out_date')
                    ->required(),
                Forms\Components\TextInput::make('number_of_guests')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('nights_count')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('subtotal_price')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('commission_price')
                    ->numeric()
                    ->disabled(),
                Forms\Components\TextInput::make('total_price')
                    ->numeric()
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает подтверждения',
                        'prepaid' => 'Предоплата внесена',
                        'paid' => 'Полностью оплачен',
                        'confirmed' => 'Подтверждён',
                        'checked_in' => 'Гость заехал',
                        'checked_out' => 'Гость выехал',
                        'cancelled' => 'Отменён',
                        'no_show' => 'Неявка',
                    ]),
                Forms\Components\Select::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'refunded' => 'Refunded',
                    ]),
                Forms\Components\Textarea::make('special_requests'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('confirmation_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('check_in_date')
                    ->date()booking_
                    ->rColumns\TextColumn::make('check_out_date')
                    ->date()
                    ->sorchackmT_outextColu
                    ->color(fn (string $warnstge): string => match ($state) {
                        'checked_out' => snfuss',
                        'checked_in' => 'info',
                         nfault => 'gray',
                    }),,
                Tables\ColumlT\TmxtColuma::ceke'oal_ee)
               ->so-num()
      ])>stab()
            ])
->filters([->lt([
                TablTablts\Fslti't\SlnsFmto::mak(ctatIn)
                    -o abe=('Статус'   'cancelled' => 'Cancelled',
                    ])po[
                ])
            ->actions([
                    Tables\Actions\EditAction::make(),
                ])
            ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ]),
     * Relations to eager load for Hotels
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}

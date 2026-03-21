<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources;

use App\Domains\Travel\Models\TravelFlight;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimeInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\NumericColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class TravelFlightResource extends Resource
{
    protected static ?string $model = TravelFlight::class;
    protected static ?string $navigationIcon = 'heroicon-o-plane';
    protected static ?string $navigationGroup = 'Travel';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Flight Information')
                ->columns(2)
                ->schema([
                    TextInput::make('airline')
                        ->required(),
                    TextInput::make('flight_number')
                        ->required()
                        ->unique(),
                    TextInput::make('departure_airport')
                        ->required(),
                    TextInput::make('arrival_airport')
                        ->required(),
                    DateTimeInput::make('departure_time')
                        ->required(),
                    DateTimeInput::make('arrival_time')
                        ->required(),
                    TextInput::make('duration_minutes')
                        ->numeric(),
                    Select::make('class')
                        ->options([
                            'economy' => 'Economy',
                            'business' => 'Business',
                            'first' => 'First Class',
                        ])
                        ->required(),
                    TextInput::make('available_seats')
                        ->numeric()
                        ->required(),
                    TextInput::make('price')
                        ->numeric()
                        ->required(),
                    TextInput::make('commission_amount')
                        ->numeric()
                        ->disabled(),
                    Select::make('status')
                        ->options([
                            'available' => 'Available',
                            'full' => 'Full',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('flight_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('airline'),
                TextColumn::make('departure_airport'),
                TextColumn::make('arrival_airport'),
                TextColumn::make('departure_time')
                    ->dateTime(),
                TextColumn::make('available_seats'),
                NumericColumn::make('price')
                    ->numeric(2),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'full' => 'Full',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('class')
                    ->options([
                        'economy' => 'Economy',
                        'business' => 'Business',
                        'first' => 'First Class',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('departure_time', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()->id);
    }
}

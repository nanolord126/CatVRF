<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources;

use App\Domains\Travel\Models\TravelTransportation;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimeInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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

final class TravelTransportationResource extends Resource
{
    protected static ?string $model = TravelTransportation::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Travel';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Transportation Information')
                ->columns(2)
                ->schema([
                    Select::make('type')
                        ->options([
                            'car_rental' => 'Car Rental',
                            'bus' => 'Bus',
                            'train' => 'Train',
                            'taxi' => 'Taxi',
                            'shuttle' => 'Shuttle',
                        ])
                        ->required(),
                    TextInput::make('provider')
                        ->required(),
                    TextInput::make('location_pickup')
                        ->required(),
                    TextInput::make('location_dropoff')
                        ->required(),
                    DateTimeInput::make('pickup_time')
                        ->required(),
                    DateTimeInput::make('dropoff_time')
                        ->required(),
                    TextInput::make('capacity')
                        ->numeric()
                        ->required(),
                    TextInput::make('available_count')
                        ->numeric()
                        ->required(),
                    TextInput::make('price')
                        ->numeric()
                        ->required(),
                    TextInput::make('commission_amount')
                        ->numeric()
                        ->disabled(),
                    TagsInput::make('features'),
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
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('provider')
                    ->searchable(),
                TextColumn::make('location_pickup'),
                TextColumn::make('location_dropoff'),
                TextColumn::make('capacity'),
                TextColumn::make('available_count'),
                NumericColumn::make('price')
                    ->numeric(2),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'car_rental' => 'Car Rental',
                        'bus' => 'Bus',
                        'train' => 'Train',
                        'taxi' => 'Taxi',
                        'shuttle' => 'Shuttle',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'full' => 'Full',
                        'cancelled' => 'Cancelled',
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', tenant()->id);
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Hotels\Filament\Resources;

use App\Domains\Hotels\Models\PricingRule;
use App\Domains\Hotels\Filament\Resources\PricingRuleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class PricingRuleResource extends Resource
{
    protected static ?string $model = PricingRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Hotels';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('room_type_id')
                    ->relationship('roomType', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'seasonal' => 'Seasonal',
                        'length_of_stay' => 'Length of Stay',
                        'advance_booking' => 'Advance Booking',
                        'last_minute' => 'Last Minute',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('date_from'),
                Forms\Components\DatePicker::make('date_to'),
                Forms\Components\TextInput::make('multiplier')
                    ->numeric()
                    ->step(0.01)
                    ->required(),
                Forms\Components\TextInput::make('min_nights')
                    ->numeric(),
                Forms\Components\TextInput::make('advance_days')
                    ->numeric(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('roomType.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'seasonal',
                        'warning' => 'length_of_stay',
                        'info' => 'advance_booking',
                        'danger' => 'last_minute',
                    ]),
                Tables\Columns\TextColumn::make('multiplier')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BooleanColumn::make('is_active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'seasonal' => 'Seasonal',
                        'length_of_stay' => 'Length of Stay',
                        'advance_booking' => 'Advance Booking',
                        'last_minute' => 'Last Minute',
                    ]),
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
            'index' => Pages\ListPricingRules::route('/'),
            'create' => Pages\CreatePricingRule::route('/create'),
            'edit' => Pages\EditPricingRule::route('/{record}/edit'),
        ];
    }
}

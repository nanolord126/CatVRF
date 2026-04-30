<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\ShortTermRentals\Filament\Resources;

use Carbon\CarbonImmutable;

use App\Filament\Resources\BaseOptimizedResource;
use App\Domains\ShortTermRentals\Models\Apartment;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages\CreateApartment;
use App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages\EditApartment;
use App\Domains\ShortTermRentals\Filament\Resources\ApartmentResource\Pages\ListApartments;

final class ApartmentResource extends BaseOptimizedResource
{
    protected static ?string $model = Apartment::class;

    protected static ?string $navigationGroup = 'Посуточная аренда';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('address')->required(),
            Forms\Components\TextInput::make('rooms')->numeric()->required(),
            Forms\Components\TextInput::make('area_sqm')->numeric()->required(),
            Forms\Components\TextInput::make('price_per_night')->numeric()->required(),
            Forms\Components\TextInput::make('deposit_amount')->numeric()->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('address')->limit(30),
            Tables\Columns\TextColumn::make('price_per_night')->money('RUB'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApartments::route('/'),
            'create' => CreateApartment::route('/create'),
            'edit' => EditApartment::route('/{record}/edit'),
        ];
    }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    /**
     * Relations to eager load for ShortTermRentals
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}

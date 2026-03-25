declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;

final /**
 * VenueResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class VenueResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-building-2';
    protected static ?string $navigationGroup = 'Entertainment';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('address')->required(),
                Forms\Components\TextInput::make('capacity')->numeric()->required(),
                Forms\Components\TextInput::make('price_per_hour')->numeric()->required(),
                Forms\Components\TextInput::make('rating')->numeric(),
            ]),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable(),
            Tables\Columns\TextColumn::make('address')->sortable(),
            Tables\Columns\TextColumn::make('capacity')->sortable(),
            Tables\Columns\TextColumn::make('price_per_hour')->money('RUB')->sortable(),
            Tables\Columns\TextColumn::make('rating')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Tenant\Resources\Entertainment\VenueResource\Pages\ListVenues::route('/')];
    }
}

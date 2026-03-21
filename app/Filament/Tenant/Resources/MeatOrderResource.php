<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\MeatShops\Models\MeatOrder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class MeatOrderResource extends Resource
{
    protected static ?string $model = MeatOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-ellipsis-horizontal';
    protected static ?string $navigationLabel = 'Meat Orders';
    protected static ?string $navigationGroup = 'MeatShops';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('weight_kg')->numeric()->required()->minValue(0.2),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('product.name')->sortable(),
                TextColumn::make('weight_kg')->numeric()->sortable(),
                TextColumn::make('total_price')->money('rub'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([SelectFilter::make('status')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with('product');
    }
}

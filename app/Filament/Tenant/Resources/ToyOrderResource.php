<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\ToysAndGames\ToysAndGames\ToysKids\Models\ToyOrder;
use Filament\Forms\Components\Checkbox;
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

final class ToyOrderResource extends Resource
{
    protected static ?string $model = ToyOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'Toy Orders';
    protected static ?string $navigationGroup = 'ToysKids';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('quantity')->numeric()->required()->minValue(1),
                Checkbox::make('gift_wrapping'),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'delivered' => 'Delivered'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('product.name')->sortable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('total_price')->money('rub'),
                TextColumn::make('gift_wrapping')->boolean(),
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

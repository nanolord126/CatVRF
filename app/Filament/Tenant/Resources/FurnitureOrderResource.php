<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Furniture\Models\FurnitureOrder;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

final class FurnitureOrderResource extends Resource
{
    protected static ?string $model = FurnitureOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Furniture Orders';
    protected static ?string $navigationGroup = 'Furniture';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('item_id')
                    ->relationship('item', 'name')
                    ->required(),
                Textarea::make('client_address')->required(),
                Checkbox::make('needs_assembly'),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'delivering' => 'Delivering', 'delivered' => 'Delivered'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('item.name')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('delivery_date')->date(),
                TextColumn::make('needs_assembly')->boolean(),
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
            ->with('item');
    }
}

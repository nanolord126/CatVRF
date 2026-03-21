<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Electronics\Models\ElectronicOrder;
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

final class ElectronicOrderResource extends Resource
{
    protected static ?string $model = ElectronicOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Electronics Orders';
    protected static ?string $navigationGroup = 'Electronics';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('serial_num')->required(),
                TextInput::make('imei_num'),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'shipped' => 'Shipped', 'delivered' => 'Delivered'])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('product.name')->sortable(),
                TextColumn::make('serial_num')->searchable(),
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

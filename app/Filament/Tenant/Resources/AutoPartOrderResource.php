<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\AutoParts\Models\AutoPartOrder;
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

final class AutoPartOrderResource extends Resource
{
    protected static ?string $model = AutoPartOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Auto Parts Orders';
    protected static ?string $navigationGroup = 'AutoParts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('part_id')
                    ->relationship('part', 'name')
                    ->required(),
                TextInput::make('vin')
                    ->required()
                    ->regex('/^[A-HJ-NPR-Z0-9]{17}$/')
                    ->helperText('17 characters, no I, O, Q'),
                TextInput::make('quantity')->numeric()->required()->minValue(1),
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
                TextColumn::make('part.name')->sortable(),
                TextColumn::make('vin')->searchable(),
                TextColumn::make('quantity')->sortable(),
                TextColumn::make('total_price')->money('rub'),
                TextColumn::make('status')->badge(),
            ])
            ->filters([SelectFilter::make('status')])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with('part');
    }
}

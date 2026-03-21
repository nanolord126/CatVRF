<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Confectionery\Models\BakeryOrder;
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

final class BakeryOrderResource extends Resource
{
    protected static ?string $model = BakeryOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-cake';
    protected static ?string $navigationLabel = 'Bakery Orders';
    protected static ?string $navigationGroup = 'Confectionery';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('cake_id')
                    ->relationship('cake', 'name')
                    ->required(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'ready' => 'Ready', 'delivered' => 'Delivered'])
                    ->required(),
                TextInput::make('total_price')->numeric()->required(),
                Textarea::make('custom_message')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('cake.name')->sortable()->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('total_price')->money('rub'),
                TextColumn::make('delivery_date')->date(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'ready' => 'Ready', 'delivered' => 'Delivered']),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getEloquentQuery()
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with('cake');
    }
}

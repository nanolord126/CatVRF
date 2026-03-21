<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionReturn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final class FashionReturnResource extends Resource
{
    protected static ?string $model = FashionReturn::class;

    protected static ?string $navigationGroup = 'Fashion';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('order_id')->relationship('order', 'order_number')->required(),
            Select::make('customer_id')->relationship('customer', 'name')->required(),
            TextInput::make('return_amount')->required()->numeric()->step(0.01),
            Select::make('reason')->options([
                'wrong_size' => 'Wrong Size',
                'damaged' => 'Damaged',
                'defective' => 'Defective',
                'not_as_described' => 'Not As Described',
                'changed_mind' => 'Changed Mind',
                'other' => 'Other',
            ])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('return_number')->searchable(),
            TextColumn::make('order.order_number'),
            TextColumn::make('customer.name'),
            TextColumn::make('return_amount')->numeric()->sortable(),
            BadgeColumn::make('status'),
            TextColumn::make('requested_at')->sortable(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}

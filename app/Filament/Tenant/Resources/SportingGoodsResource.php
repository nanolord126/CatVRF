<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\SportingGoods\Models\SportProduct;

class SportingGoodsResource extends Resource
{
    protected static ?string $model = SportProduct::class;
    protected static ?string $navigationIcon = "heroicon-o-shopping-bag";

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }
}

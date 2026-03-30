<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerShopResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FlowerShop::class;
        protected static ?string $slug = 'flower-shops';
        protected static ?string $navigationGroup = 'Flowers';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('city')->required(),
                Forms\Components\TextInput::make('address')->required(),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\Toggle::make('is_active'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('rating'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])->actions([
                Tables\Actions\EditAction::make(),
            ]);
        }
}

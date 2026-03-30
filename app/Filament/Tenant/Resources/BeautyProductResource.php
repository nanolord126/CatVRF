<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyProductResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = BeautyProduct::class;

        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

        protected static ?string $navigationLabel = 'Товары';

        protected static ?string $navigationGroup = 'Beauty';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Select::make('salon_id')
                    ->relationship('salon', 'name')
                    ->required()
                    ->label('Салон'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Название'),
                Forms\Components\TextInput::make('sku')
                    ->maxLength(100)
                    ->label('Артикул'),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required()
                    ->label('Цена'),
                Forms\Components\TextInput::make('current_stock')
                    ->numeric()
                    ->default(0)
                    ->label('Остаток'),
                Forms\Components\Select::make('consumable_type')
                    ->options([
                        'none' => 'Не расходник',
                        'low' => 'Малый расход',
                        'medium' => 'Средний расход',
                        'high' => 'Высокий расход',
                    ])
                    ->default('none')
                    ->label('Тип расхода'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBeautyProduct::route('/'),
                'create' => Pages\\CreateBeautyProduct::route('/create'),
                'edit' => Pages\\EditBeautyProduct::route('/{record}/edit'),
                'view' => Pages\\ViewBeautyProduct::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBeautyProduct::route('/'),
                'create' => Pages\\CreateBeautyProduct::route('/create'),
                'edit' => Pages\\EditBeautyProduct::route('/{record}/edit'),
                'view' => Pages\\ViewBeautyProduct::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBeautyProduct::route('/'),
                'create' => Pages\\CreateBeautyProduct::route('/create'),
                'edit' => Pages\\EditBeautyProduct::route('/{record}/edit'),
                'view' => Pages\\ViewBeautyProduct::route('/{record}'),
            ];
        }
}

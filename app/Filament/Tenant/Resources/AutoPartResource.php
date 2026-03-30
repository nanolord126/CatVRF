<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoPartResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = AutoPart::class;

        protected static ?string $navigationIcon = 'heroicon-o-cog';

        protected static ?string $navigationGroup = 'Автосервис (СТО)';

        protected static ?string $label = 'Запчасть';

        protected static ?string $pluralLabel = 'Склад запчастей';

        protected static ?string $slug = 'auto/parts';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Информация о запчасти')
                        ->schema([
                            Forms\Components\TextInput::make('sku')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->label('SKU / Артикул'),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->label('Наименование'),
                            Forms\Components\TextInput::make('brand')
                                ->label('Бренд'),
                            Forms\Components\TextInput::make('price_kopecks')
                                ->numeric()
                                ->required()
                                ->label('Цена (коп)'),
                        ])->columns(2),

                    Forms\Components\Section::make('Складские остатки')
                        ->schema([
                            Forms\Components\TextInput::make('current_stock')
                                ->numeric()
                                ->default(0)
                                ->label('В наличии'),
                            Forms\Components\TextInput::make('min_stock_threshold')
                                ->numeric()
                                ->default(5)
                                ->label('Мин. порог'),
                        ])->columns(2),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListAutoPart::route('/'),
                'create' => Pages\\CreateAutoPart::route('/create'),
                'edit' => Pages\\EditAutoPart::route('/{record}/edit'),
                'view' => Pages\\ViewAutoPart::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListAutoPart::route('/'),
                'create' => Pages\\CreateAutoPart::route('/create'),
                'edit' => Pages\\EditAutoPart::route('/{record}/edit'),
                'view' => Pages\\ViewAutoPart::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListAutoPart::route('/'),
                'create' => Pages\\CreateAutoPart::route('/create'),
                'edit' => Pages\\EditAutoPart::route('/{record}/edit'),
                'view' => Pages\\ViewAutoPart::route('/{record}'),
            ];
        }
}

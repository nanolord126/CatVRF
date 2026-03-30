<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FinancesResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FinancialRecord::class;

        protected static ?string $navigationIcon = 'heroicon-o-banknotes';

        protected static ?string $navigationGroup = 'Finance';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->description('Базовые сведения об объекте')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                                    TextInput::make('description')
                        ->required()
                        ->maxLength(255),
                                    Select::make('type')
                        ->required()
                        ->searchable(),
                                    TextInput::make('amount')
                        ->required()
                        ->maxLength(255),
                                    Select::make('status')
                        ->required()
                        ->searchable(),
                                    DatePicker::make('date')
                        ->required(),
                                ]),
                        ]),

                    Section::make('Дополнительно')
                        ->description('Расширенные параметры')
                        ->collapsed()
                        ->schema([
                            Grid::make(2)
                                ->schema([]),
                        ]),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFinances::route('/'),
                'create' => Pages\\CreateFinances::route('/create'),
                'edit' => Pages\\EditFinances::route('/{record}/edit'),
                'view' => Pages\\ViewFinances::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFinances::route('/'),
                'create' => Pages\\CreateFinances::route('/create'),
                'edit' => Pages\\EditFinances::route('/{record}/edit'),
                'view' => Pages\\ViewFinances::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListFinances::route('/'),
                'create' => Pages\\CreateFinances::route('/create'),
                'edit' => Pages\\EditFinances::route('/{record}/edit'),
                'view' => Pages\\ViewFinances::route('/{record}'),
            ];
        }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MasterResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Master::class;

        protected static ?string $navigationIcon = 'heroicon-o-user-group';

        protected static ?string $navigationLabel = 'Мастера';

        protected static ?string $navigationGroup = 'Beauty';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Select::make('salon_id')
                    ->relationship('salon', 'name')
                    ->required()
                    ->label('Салон'),
                Forms\Components\TextInput::make('full_name')
                    ->required()
                    ->maxLength(255)
                    ->label('ФИО мастера'),
                Forms\Components\KeyValue::make('specialization')
                    ->label('Специализация'),
                Forms\Components\TextInput::make('experience_years')
                    ->numeric()
                    ->minValue(0)
                    ->label('Опыт (лет)'),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(20)
                    ->label('Телефон'),
                Forms\Components\TextInput::make('rating')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5)
                    ->default(0)
                    ->label('Рейтинг'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
            ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListMaster::route('/'),
                'create' => Pages\\CreateMaster::route('/create'),
                'edit' => Pages\\EditMaster::route('/{record}/edit'),
                'view' => Pages\\ViewMaster::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListMaster::route('/'),
                'create' => Pages\\CreateMaster::route('/create'),
                'edit' => Pages\\EditMaster::route('/{record}/edit'),
                'view' => Pages\\ViewMaster::route('/{record}'),
            ];
        }
}

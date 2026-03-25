declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\HealthyFood\Models\HealthyFood;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final /**
 * HealthyFoodResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class HealthyFoodResource extends Resource
{
    protected static ?string $model = HealthyFood::class;
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationGroup = 'Food';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('sku')->required()->unique(ignoreRecord: true),
            Select::make('diet_type')->options([
                'vegan' => 'Веганское', 'keto' => 'Кето', 'protein' => 'Протеиновое',
                'balanced' => 'Сбалансированное', 'lowcarb' => 'Низкоуглеводное',
            ])->required(),
            TextInput::make('calories')->numeric(),
            TextInput::make('protein_g')->numeric(),
            TextInput::make('carbs_g')->numeric(),
            TextInput::make('fat_g')->numeric(),
            TextInput::make('price')->numeric()->required(),
            TextInput::make('current_stock')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('diet_type'),
            TextColumn::make('calories'),
            TextColumn::make('price')->formatStateUsing(fn($s) => $s . ' ₽'),
            TextColumn::make('current_stock'),
            TextColumn::make('rating'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make(),
        ])->bulkActions([
            \Filament\Tables\Actions\BulkDeleteAction::make(),
        ]);
    }
}

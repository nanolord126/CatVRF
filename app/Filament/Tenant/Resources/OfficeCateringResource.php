<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\OfficeCatering\Models\OfficeCatering;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class OfficeCateringResource extends Resource
{
    protected static ?string $model = OfficeCatering::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Food';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('sku')->required()->unique(ignoreRecord: true),
            Select::make('meal_type')->options([
                'breakfast' => 'Завтрак', 'lunch' => 'Обед', 'dinner' => 'Ужин',
                'snacks' => 'Снеки', 'combo' => 'Комбо',
            ])->required(),
            TextInput::make('servings')->numeric(),
            TextInput::make('price_per_serving')->numeric(),
            TextInput::make('total_price')->numeric()->required(),
            TextInput::make('current_stock')->numeric(),
            TextInput::make('min_order')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('meal_type'),
            TextColumn::make('servings'),
            TextColumn::make('total_price')->formatStateUsing(fn($s) => $s . ' ₽'),
            TextColumn::make('min_order'),
            TextColumn::make('rating'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make(),
        ])->bulkActions([
            \Filament\Tables\Actions\BulkDeleteAction::make(),
        ]);
    }
}

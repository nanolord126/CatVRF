<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ConstructionMaterials;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ConstructionMaterialResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = ConstructionMaterial::class;
        protected static ?string $slug = 'construction-materials';
        protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
        protected static ?string $navigationGroup = 'Construction';

        public static function form(Form $form): Form
        {
            return $form->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('sku')->required()->unique()->maxLength(50),
                Select::make('category')->required()->options([
                    'cement' => 'Цемент',
                    'bricks' => 'Кирпич',
                    'sand' => 'Песок',
                    'gravel' => 'Щебень',
                    'steel' => 'Сталь',
                    'timber' => 'Дерево',
                    'paint' => 'Краска',
                    'tools' => 'Инструменты',
                    'hardware' => 'Крепёж',
                ]),
                Textarea::make('description')->maxLength(1000),
                TextInput::make('current_stock')->numeric()->required(),
                TextInput::make('min_stock_threshold')->numeric()->required(),
                TextInput::make('max_stock_threshold')->numeric()->required(),
                TextInput::make('price')->numeric()->required()->hint('Цена в копейках'),
                Select::make('unit')->options([
                    'bag' => 'Мешок',
                    'ton' => 'Тонна',
                    'meter' => 'Метр',
                    'box' => 'Коробка',
                    'piece' => 'Штука',
                    'liter' => 'Литр',
                ])->required(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')->searchable()->sortable(),
                    TextColumn::make('sku')->badge(),
                    TextColumn::make('category')->badge(),
                    TextColumn::make('current_stock')->numeric()->label('Остаток'),
                    BadgeColumn::make('current_stock')
                        ->color(fn (Model $record) => $record->isLowStock() ? 'danger' : 'success')
                        ->label('Статус')
                        ->formatStateUsing(fn (Model $record) => $record->isLowStock() ? 'Низкий' : 'ОК'),
                    TextColumn::make('price')->numeric()->label('Цена'),
                ])
                ->filters([
                    SelectFilter::make('category'),
                ])
                ->actions([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListConstructionMaterials::route('/'),
                'create' => Pages\CreateConstructionMaterial::route('/create'),
                'edit' => Pages\EditConstructionMaterial::route('/{record}/edit'),
            ];
        }
}

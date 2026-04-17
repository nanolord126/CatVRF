<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HobbyAndCraft;

    use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Tables;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class CraftSupplyResource extends Resource
    {
        protected static ?string $model = CraftSupply::class;
        protected static ?string $navigationIcon = 'heroicon-m-sparkles';
        protected static ?string $navigationGroup = 'Hobby & Art';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🎨 Материалы и расходники')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        Select::make('category')->label('Категория')->options(['painting' => 'Рисование', 'knitting' => 'Вязание', 'sewing' => 'Шитьё', 'jewelry' => 'Украшения', 'woodwork' => 'Дерево', 'pottery' => 'Керамика', 'other' => 'Прочее'])->required()->columnSpan(1),
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('quantity_in_stock')->label('В наличии')->numeric()->required()->columnSpan(1),
                        TextInput::make('unit')->label('Единица (шт/м/кг)')->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('image')->label('Фото')->image()->directory('craft')->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Toggle::make('is_eco_friendly')->label('♻️ Экологичный')->columnSpan(1),
                        Toggle::make('is_professional')->label('👨‍🎨 Профессиональный')->columnSpan(1),
                        Toggle::make('is_organic')->label('🌿 Натуральный')->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                    ])->columns(4),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                    ])->columns('full'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Материал')->searchable()->sortable()->limit(40),
                BadgeColumn::make('category')->label('Категория')->color(fn ($state) => match($state) { 'painting' => 'blue', 'knitting' => 'purple', 'sewing' => 'pink', 'jewelry' => 'yellow', 'woodwork' => 'orange', default => 'gray' }),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100),
                TextColumn::make('quantity_in_stock')->label('Остаток')->numeric()->alignment('center')->badge()->color(fn ($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                BooleanColumn::make('is_professional')->label('👨‍🎨')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('category')->label('Категория')->options(['painting' => 'Рисование', 'knitting' => 'Вязание', 'sewing' => 'Шитьё', 'jewelry' => 'Украшения'])->multiple(),
                TernaryFilter::make('is_professional')->label('Профессиональный'),
                TrashedFilter::make(),
            ])->actions([
                ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]),
            ])->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\HobbyAndCraft\Pages\ListSupplies::route('/'),
                'create' => \App\Filament\Tenant\Resources\HobbyAndCraft\Pages\CreateSupply::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\HobbyAndCraft\Pages\EditSupply::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

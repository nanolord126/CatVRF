<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VeganProducts;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VeganProductResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\Hidden, Components\RichEditor, Components\FileUpload};
    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class VeganProductResource extends Resource
    {
        protected static ?string $model = VeganProduct::class;
        protected static ?string $navigationIcon = 'heroicon-m-leaf';
        protected static ?string $navigationGroup = 'Food & Delivery';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🌿 Веган-продукт')
                    ->icon('heroicon-m-leaf')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        Select::make('category')->label('Категория')->options(['meat_substitute' => 'Мясозаменитель', 'dairy' => 'Молочные', 'snacks' => 'Снеки', 'baking' => 'Выпечка', 'sauces' => 'Соусы', 'beverages' => 'Напитки'])->required()->columnSpan(1),
                        TextInput::make('brand')->label('Бренд')->columnSpan(1),
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('stock')->label('В наличии')->numeric()->required()->columnSpan(1),
                        Select::make('certification')->label('Сертификация')->options(['vegan' => 'Веган', 'vegetarian' => 'Вегетарианское', 'gluten_free' => 'Без глютена'])->multiple()->columnSpan(2),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('image')->label('Фото')->image()->directory('vegan')->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_organic')->label('🌾 Органическое')->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐')->columnSpan(1),
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
                TextColumn::make('name')->label('Продукт')->searchable()->sortable()->icon('heroicon-m-leaf')->limit(40),
                BadgeColumn::make('category')->label('Категория')->color(fn ($state) => match($state) { 'meat_substitute' => 'red', 'dairy' => 'blue', 'snacks' => 'yellow', 'baking' => 'orange', 'sauces' => 'green', 'beverages' => 'cyan' }),
                TextColumn::make('brand')->label('Бренд')->searchable(),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('stock')->label('Остаток')->numeric()->alignment('center')->badge()->color(fn ($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('is_organic')->label('🌾')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('category')->label('Категория')->options(['meat_substitute' => 'Мясо', 'dairy' => 'Молочные', 'snacks' => 'Снеки', 'baking' => 'Выпечка'])->multiple(),
                TernaryFilter::make('is_organic')->label('Органическое'),
                Filter::make('low_stock')->label('Кончается')->query(fn (Builder $query) => $query->where('stock', '<', 5)),
                TrashedFilter::make(),
            ])->actions([
                ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]),
            ])->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->icon('heroicon-m-check-circle')->color('success')->action(function ($records) { $records->each(fn ($r) => $r->update(['is_active' => true])); })->deselectRecordsAfterCompletion()]),
            ])->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\VeganProducts\Pages\ListProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\VeganProducts\Pages\CreateProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\VeganProducts\Pages\EditProduct::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

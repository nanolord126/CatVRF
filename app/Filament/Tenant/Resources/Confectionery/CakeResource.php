<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Confectionery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CakeResource extends Model
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

    final class CakeResource extends Resource
    {
        protected static ?string $model = Cake::class;
        protected static ?string $navigationIcon = 'heroicon-m-cake';
        protected static ?string $navigationGroup = 'Food & Confectionery';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🍰 Основная информация')
                    ->icon('heroicon-m-cake')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        Select::make('cake_type')->label('Тип')->options(['classic' => 'Классический', 'modern' => 'Модерн', 'artistic' => 'Арт', 'custom' => 'Кастомный'])->required()->columnSpan(1),
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('image')->label('Фото')->image()->directory('confectionery')->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('flavour')->label('Вкус')->options(['chocolate' => 'Шоколад', 'vanilla' => 'Ваниль', 'fruit' => 'Фрукты', 'nuts' => 'Орехи'])->columnSpan(1),
                        TextInput::make('servings')->label('Порций')->numeric()->columnSpan(1),
                        Toggle::make('is_vegan')->label('♻️ Веган')->columnSpan(1),
                        Toggle::make('gluten_free')->label('🌾 Без глютена')->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг и статус')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐')->columnSpan(1),
                        Toggle::make('is_bestseller')->label('🔥')->columnSpan(1),
                    ])->columns(4),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                        Hidden::make('business_group_id')->default(fn () => filament()->getTenant()?->active_business_group_id),
                    ])->columns('full'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Торт')->searchable()->sortable()->icon('heroicon-m-cake')->limit(40),
                BadgeColumn::make('cake_type')->label('Тип')->color(fn ($state) => match($state) { 'classic' => 'blue', 'modern' => 'purple', 'artistic' => 'pink', 'custom' => 'orange', default => 'gray' }),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('servings')->label('Порций')->numeric()->alignment('center'),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_bestseller')->label('🔥')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('cake_type')->label('Тип')->options(['classic' => 'Классический', 'modern' => 'Модерн', 'artistic' => 'Арт'])->multiple(),
                TernaryFilter::make('is_featured')->label('Рекомендуемый'),
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
                'index' => \App\Filament\Tenant\Resources\Confectionery\Pages\ListCakes::route('/'),
                'create' => \App\Filament\Tenant\Resources\Confectionery\Pages\CreateCake::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Confectionery\Pages\EditCake::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

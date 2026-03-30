<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FarmDirect;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FarmProductResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, TextInput, Select, RichEditor, FileUpload, Toggle, TagsInput, Hidden, Grid};
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class FarmProductResource extends Resource
    {
        protected static ?string $model = \App\Domains\FarmDirect\Models\FarmProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-leaf';
        protected static ?string $navigationGroup = 'FarmDirect';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('farm_name')->label('Хозяйство')->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Фото')->image()->directory('farm')->columnSpan(1),
                        FileUpload::make('photos')->label('Галерея')->image()->multiple()->directory('farm')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и происхождение')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'vegetables' => '🥬 Овощи',
                                'fruits' => '🍎 Фрукты',
                                'berries' => '🫐 Ягоды',
                                'dairy' => '🥛 Молочное',
                                'meat' => '🥩 Мясо',
                                'eggs' => '🥚 Яйца',
                                'honey' => '🍯 Мёд',
                                'grains' => '🌾 Зёрна',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('region')->label('Регион производства')->columnSpan(2),

                        Select::make('certification')
                            ->label('Сертификация')
                            ->options([
                                'organic' => '🌱 Органик (ГОСТ)',
                                'eco' => '♻️ Эко (ГОСТ)',
                                'conventional' => 'Обычное',
                            ])
                            ->columnSpan(2),

                        TextInput::make('harvest_date')->label('Дата урожая')->type('date')->columnSpan(2),

                        TagsInput::make('growing_methods')->label('Методы выращивания')->columnSpan('full'),
                    ])->columns(4),

                Section::make('Цена и урожайность')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('current_stock')->label('На складе (кг)')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('min_stock_threshold')->label('Мин. запас (кг)')->numeric()->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('weight_kg')->label('Вес (кг)')->numeric()->columnSpan(1),
                        TextInput::make('shelf_life_days')->label('Срок годности (дн)')->numeric()->columnSpan(1),
                        Select::make('storage_condition')->label('Условия хранения')->options(['room' => 'Комнатная', 'cool' => 'Прохладное', 'frozen' => 'Морозильник'])->columnSpan(2),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('repeat_purchase_rate')->label('Повторных покупок %')->numeric()->disabled()->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('is_seasonal')->label('🍂 Сезонный')->columnSpan(2),
                    ])->columns(4),

                Section::make('Служебная')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                        Hidden::make('business_group_id')->default(fn () => filament()->getTenant()?->active_business_group_id),
                    ]),
            ]);
        }

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table->columns([
                ImageColumn::make('main_photo')->label('Фото')->height(50),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->limit(30),
                TextColumn::make('farm_name')->label('Хозяйство')->searchable(),
                BadgeColumn::make('category')->label('Категория')->color('success'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                BadgeColumn::make('certification')->label('Сертификация')->color(fn ($state) => $state === 'organic' ? 'success' : 'info'),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4.5 ? 'success' : 'warning'),
                BooleanColumn::make('is_seasonal')->label('🍂'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')->options(['vegetables' => 'Овощи', 'fruits' => 'Фрукты', 'berries' => 'Ягоды'])->multiple(),
                SelectFilter::make('certification')->label('Сертификация')->options(['organic' => 'Органик', 'eco' => 'Эко'])->multiple(),
                TernaryFilter::make('is_seasonal')->label('Сезонный'),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_active' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\FarmDirect\FarmProductResource\Pages\ListFarmProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\FarmDirect\FarmProductResource\Pages\CreateFarmProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\FarmDirect\FarmProductResource\Pages\EditFarmProduct::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

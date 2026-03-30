<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Electronics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicsProductResource extends Model
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

    final class ElectronicsProductResource extends Resource
    {
        protected static ?string $model = \App\Domains\Electronics\Models\ElectronicsProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-bolt';
        protected static ?string $navigationGroup = 'Electronics';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('brand')->label('Бренд')->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Фото')->image()->directory('electronics')->columnSpan(1),
                        FileUpload::make('photos')->label('Галерея')->image()->multiple()->directory('electronics')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и характеристики')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'smartphones' => '📱 Смартфоны',
                                'laptops' => '💻 Ноутбуки',
                                'tablets' => '📱 Планшеты',
                                'headphones' => '🎧 Наушники',
                                'cameras' => '📷 Камеры',
                                'smartwatch' => '⌚ Смарт-часы',
                                'gaming' => '🎮 Игровые приставки',
                                'accessories' => '🔌 Аксессуары',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('model')->label('Модель')->columnSpan(2),

                        TagsInput::make('features')->label('Характеристики')->columnSpan('full'),

                        TextInput::make('warranty_months')->label('Гарантия (мес)')->numeric()->columnSpan(2),

                        TextInput::make('battery_hours')->label('Время работы (ч)')->numeric()->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и запасы')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('current_stock')->label('На складе')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('min_stock_threshold')->label('Мин. запас')->numeric()->columnSpan(1),
                        Select::make('stock_status')->label('Статус')->options(['in_stock' => '✓ В наличии', 'low_stock' => '⚠️ Мало', 'out_of_stock' => '❌ Нет', 'pre_order' => '📦 Предзаказ'])->columnSpan(2),
                    ])->columns(4),

                Section::make('Технические спецификации')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('processor')->label('Процессор')->columnSpan(2),
                        TextInput::make('ram_gb')->label('ОЗУ (ГБ)')->numeric()->columnSpan(1),
                        TextInput::make('storage_gb')->label('Хранилище (ГБ)')->numeric()->columnSpan(1),
                        TextInput::make('screen_size')->label('Диагональ экрана')->columnSpan(2),
                        TextInput::make('weight_kg')->label('Вес (кг)')->numeric()->columnSpan(2),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('purchase_count')->label('Куплено')->numeric()->disabled()->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('is_new')->label('🆕 Новинка')->columnSpan(2),
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
                TextColumn::make('name')->label('Название')->searchable()->sortable()->limit(35),
                TextColumn::make('model')->label('Модель')->searchable(),
                BadgeColumn::make('category')->label('Категория')->color('info'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                BadgeColumn::make('stock_status')->label('Запас')->color(fn ($state) => $state === 'in_stock' ? 'success' : ($state === 'low_stock' ? 'warning' : 'danger')),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                TextColumn::make('warranty_months')->label('Гарантия')->numeric(),
                BooleanColumn::make('is_featured')->label('⭐'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')->options(['smartphones' => 'Смартфоны', 'laptops' => 'Ноутбуки', 'headphones' => 'Наушники'])->multiple(),
                SelectFilter::make('stock_status')->label('Запас')->options(['in_stock' => 'В наличии', 'pre_order' => 'Предзаказ'])->multiple(),
                TernaryFilter::make('is_featured')->label('Рекомендуемый'),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_active' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Electronics\ElectronicsProductResource\Pages\ListElectronicsProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Electronics\ElectronicsProductResource\Pages\CreateElectronicsProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Electronics\ElectronicsProductResource\Pages\EditElectronicsProduct::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

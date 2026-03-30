<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlowerProductResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, TextInput, Select, RichEditor, FileUpload, Toggle, Hidden, Grid};
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;
    use App\Domains\Flowers\Models\FlowerProduct;

    final class FlowerProductResource extends Resource
    {
        protected static ?string $model = FlowerProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-leaf';
        protected static ?string $navigationGroup = 'Flowers';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-leaf')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('farm_origin')->label('Ферма/происхождение')->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Фото')->image()->directory('flowers')->columnSpan(1),
                        FileUpload::make('photos')->label('Галерея')->image()->multiple()->directory('flowers')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и назначение')
                    ->icon('heroicon-m-gift')
                    ->schema([
                        Select::make('flower_type')
                            ->label('Вид цветов')
                            ->options(['roses' => '🌹 Розы', 'tulips' => '🌷 Тюльпаны', 'sunflowers' => '🌻 Подсолнухи', 'lilies' => '⚪ Лилии', 'peonies' => '🌸 Пионы', 'gerberas' => '🌸 Герберы', 'mixed' => '💐 Микс', 'exotic' => '✨ Экзотические'])
                            ->required()
                            ->columnSpan(2),
                        Select::make('occasion')
                            ->label('Назначение')
                            ->options(['birthday' => '🎂 День рождения', 'wedding' => '💒 Свадьба', 'condolences' => '⚫ Соболезнования', 'celebration' => '🎉 Праздник', 'love' => '💝 Признание', 'sympathy' => '🤝 Поддержка'])
                            ->columnSpan(2),
                        TextInput::make('color')->label('Цвет(ы)')->columnSpan(2),
                        TextInput::make('vase_life_days')->label('Жизнь букета (дн)')->numeric()->columnSpan(2),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('stem_length_cm')->label('Длина стебля (см)')->numeric()->columnSpan(2),
                        TextInput::make('growing_region')->label('Регион выращивания')->columnSpan(2),
                        Toggle::make('eco_friendly')->label('♻️ Экологичный')->columnSpan(1),
                        Toggle::make('local_grown')->label('Местное выращивание')->columnSpan(1),
                    ])->columns(4),

                Section::make('Цена и доставка')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        Toggle::make('same_day_delivery')->label('Доставка в день')->columnSpan(2),
                        Toggle::make('special_packaging')->label('Специальная упаковка')->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('is_seasonal')->label('Сезонный')->columnSpan(2),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('purchase_count')->label('Куплено')->numeric()->disabled()->columnSpan(2),
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
                TextColumn::make('name')->searchable()->sortable()->limit(30),
                TextColumn::make('sku'),
                BadgeColumn::make('flower_type')->label('Тип')->color('info'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('current_stock')->badge()->color('success'),
                TextColumn::make('rating')->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                TextColumn::make('vase_life_days')->label('Жизнь (дн)'),
                TextColumn::make('stem_length_cm')->label('Длина (см)'),
                BooleanColumn::make('eco_friendly')->label('♻️'),
                BooleanColumn::make('same_day_delivery')->label('🚚'),
                BooleanColumn::make('is_seasonal')->label('Сезонный'),
                BooleanColumn::make('is_featured')->label('⭐'),
                BooleanColumn::make('is_active')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('flower_type')->label('Тип')->options(['roses' => 'Розы', 'tulips' => 'Тюльпаны', 'sunflowers' => 'Подсолнухи'])->multiple(),
                SelectFilter::make('occasion')->label('Назначение')->options(['birthday' => 'День рождения', 'wedding' => 'Свадьба', 'celebration' => 'Праздник'])->multiple(),
                TernaryFilter::make('is_seasonal')->label('Сезонный'),
                TernaryFilter::make('eco_friendly')->label('Экологичный'),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_active' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Flowers\Pages\ListFlowerProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Flowers\Pages\CreateFlowerProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Flowers\Pages\EditFlowerProduct::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

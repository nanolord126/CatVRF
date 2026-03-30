<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Vintage;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class VintageItemResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    Section, TextInput, Select, RichEditor, FileUpload, Toggle, TagsInput, Hidden, Grid};
    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter, Filter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class VintageItemResource extends Resource
    {
        protected static ?string $model = \App\Domains\Vintage\Models\VintageItem::class;
        protected static ?string $navigationIcon = 'heroicon-o-clock';
        protected static ?string $navigationGroup = 'Vintage';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('item_id')->label('Артикул')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('origin')->label('Происхождение')->columnSpan(1),
                        RichEditor::make('description')->label('История и описание')->columnSpan('full'),
                        RichEditor::make('condition_notes')->label('Состояние')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Главное фото')->image()->directory('vintage')->columnSpan(1),
                        FileUpload::make('photos')->label('Галерея')->image()->multiple()->directory('vintage')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и период')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'furniture' => '🪑 Мебель',
                                'decor' => '🎨 Декор',
                                'clothing' => '👗 Одежда',
                                'accessories' => '👜 Аксессуары',
                                'jewelry' => '💍 Украшения',
                                'electronics' => '📻 Электроника',
                                'books' => '📚 Книги',
                                'collectibles' => '🎁 Коллекционные',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Select::make('era')
                            ->label('Период')
                            ->options([
                                '1920-1950' => '1920-1950',
                                '1950-1970' => '1950-1970',
                                '1970-1990' => '1970-1990',
                                '1990-2000' => '1990-2000',
                                '2000-2010' => '2000-2010',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('designer_maker')->label('Дизайнер/Производитель')->columnSpan(2),

                        TextInput::make('production_year')->label('Год выпуска')->numeric()->columnSpan(2),

                        TagsInput::make('style')->label('Стиль')->columnSpan('full'),
                    ])->columns(4),

                Section::make('Состояние и редкость')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        Select::make('condition')
                            ->label('Состояние')
                            ->options([
                                'mint' => 'Идеальное (Mint)',
                                'excellent' => 'Отличное',
                                'very_good' => 'Хорошее',
                                'good' => 'Приемлемое',
                                'fair' => 'Требует восстановления',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Select::make('rarity')
                            ->label('Редкость')
                            ->options([
                                'extremely_rare' => '🔴 Исключительная редкость',
                                'very_rare' => '🟠 Очень редкое',
                                'rare' => '🟡 Редкое',
                                'uncommon' => '🟢 Необычное',
                                'common' => '⚪ Распространённое',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('historical_value')->label('Историческая ценность')->numeric()->columnSpan(2),

                        Toggle::make('has_authentication')->label('✓ Имеет сертификат подлинности')->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и стоимость')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('estimated_value')->label('Оценочная стоимость (₽)')->numeric()->columnSpan(2),
                        TextInput::make('acquisition_year')->label('Год приобретения')->numeric()->columnSpan(1),
                        TextInput::make('acquisition_price')->label('Цена приобретения (₽)')->numeric()->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг и популярность')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('view_count')->label('Просмотров')->numeric()->disabled()->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('requires_careful_shipping')->label('📦 Требует тщательной упаковки')->columnSpan(2),
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
                ImageColumn::make('main_photo')->label('Фото')->height(60),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->limit(30),
                BadgeColumn::make('category')->label('Категория')->color('info'),
                BadgeColumn::make('era')->label('Период')->color('warning'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                BadgeColumn::make('condition')->label('Состояние')->color(fn ($state) => match($state) {
                    'mint', 'excellent' => 'success',
                    'very_good', 'good' => 'warning',
                    default => 'danger',
                }),
                BadgeColumn::make('rarity')->label('Редкость')->color(fn ($state) => str_starts_with($state, 'extremely') ? 'danger' : 'info'),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4.5 ? 'success' : 'warning'),
                BooleanColumn::make('has_authentication')->label('✓ Сертифицировано'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')->options(['furniture' => 'Мебель', 'decor' => 'Декор', 'jewelry' => 'Украшения'])->multiple(),
                SelectFilter::make('era')->label('Период')->options(['1920-1950' => '1920-1950', '1950-1970' => '1950-1970'])->multiple(),
                SelectFilter::make('condition')->label('Состояние')->options(['mint' => 'Идеальное', 'excellent' => 'Отличное'])->multiple(),
                TernaryFilter::make('has_authentication')->label('Сертифицировано'),
                Filter::make('high_value')->label('Ценное (>50k₽)')->query(fn (Builder $query) => $query->where('price', '>', 5000000)),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('verify')->label('Верифицировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['has_authentication' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Vintage\VintageItemResource\Pages\ListVintageItems::route('/'),
                'create' => \App\Filament\Tenant\Resources\Vintage\VintageItemResource\Pages\CreateVintageItem::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Vintage\VintageItemResource\Pages\EditVintageItem::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

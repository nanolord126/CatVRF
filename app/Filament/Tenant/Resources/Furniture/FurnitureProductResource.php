<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Furniture;

    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class FurnitureProductResource extends Resource
    {
        protected static ?string $model = \App\Domains\Furniture\Models\FurnitureProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-home';
        protected static ?string $navigationGroup = 'Furniture';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('designer')->label('Дизайнер')->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Главное фото')->image()->directory('furniture')->columnSpan(1),
                        FileUpload::make('photos')->label('3D и галерея')->image()->multiple()->directory('furniture')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и стиль')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'seating' => '🪑 Сидение',
                                'tables' => '🪑 Столы',
                                'storage' => '📦 Хранение',
                                'beds' => '🛏️ Кровати',
                                'shelves' => '📚 Стеллажи',
                                'lighting' => '💡 Освещение',
                                'decor' => '🎨 Декор',
                                'outdoor' => '🌳 Уличная',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Select::make('style')
                            ->label('Стиль')
                            ->options([
                                'minimalist' => 'Минимализм',
                                'scandinavian' => 'Скандинав',
                                'loft' => 'Лофт',
                                'classic' => 'Классика',
                                'modern' => 'Модерн',
                                'boho' => 'Бохо',
                                'industrial' => 'Индустриальный',
                                'rustic' => 'Рустик',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TagsInput::make('features')->label('Особенности')->columnSpan('full'),

                        Select::make('material')
                            ->label('Материал')
                            ->options([
                                'wood' => 'Дерево',
                                'metal' => 'Металл',
                                'plastic' => 'Пластик',
                                'leather' => 'Кожа',
                                'fabric' => 'Ткань',
                                'glass' => 'Стекло',
                                'composite' => 'Композит',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('color')->label('Цвет')->columnSpan(2),
                    ])->columns(4),

                Section::make('Размеры и вес')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('width_cm')->label('Ширина (см)')->numeric()->columnSpan(1),
                        TextInput::make('depth_cm')->label('Глубина (см)')->numeric()->columnSpan(1),
                        TextInput::make('height_cm')->label('Высота (см)')->numeric()->columnSpan(1),
                        TextInput::make('weight_kg')->label('Вес (кг)')->numeric()->columnSpan(1),
                    ])->columns(4),

                Section::make('Цена и наличие')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('current_stock')->label('На складе')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('min_stock_threshold')->label('Мин. запас')->numeric()->columnSpan(1),
                        Toggle::make('is_custom_order')->label('Под заказ')->columnSpan(2),
                    ])->columns(4),

                Section::make('Доставка и сборка')
                    ->icon('heroicon-m-truck')
                    ->schema([
                        Toggle::make('requires_assembly')->label('Требуется сборка')->columnSpan(1),
                        Toggle::make('delivery_available')->label('Доставка')->columnSpan(1),
                        TextInput::make('assembly_time_hours')->label('Время сборки (ч)')->numeric()->columnSpan(2),
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
                        Toggle::make('eco_friendly')->label('♻️ Экологичный')->columnSpan(2),
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
                BadgeColumn::make('category')->label('Категория')->color('info'),
                BadgeColumn::make('style')->label('Стиль')->color('warning'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('width_cm')->label('Размеры (Ш×В×Г)')->formatStateUsing(fn ($record) => $record->width_cm . '×' . $record->height_cm . '×' . $record->depth_cm),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4.3 ? 'success' : 'warning'),
                TextColumn::make('purchase_count')->label('Куплено')->numeric(),
                BooleanColumn::make('eco_friendly')->label('♻️'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')->options(['seating' => 'Сидение', 'tables' => 'Столы', 'storage' => 'Хранение'])->multiple(),
                SelectFilter::make('style')->label('Стиль')->options(['minimalist' => 'Минимализм', 'scandinavian' => 'Скандинав', 'loft' => 'Лофт'])->multiple(),
                SelectFilter::make('material')->label('Материал')->options(['wood' => 'Дерево', 'metal' => 'Металл'])->multiple(),
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
                'index' => \App\Filament\Tenant\Resources\Furniture\FurnitureProductResource\Pages\ListFurnitureProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Furniture\FurnitureProductResource\Pages\CreateFurnitureProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Furniture\FurnitureProductResource\Pages\EditFurnitureProduct::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

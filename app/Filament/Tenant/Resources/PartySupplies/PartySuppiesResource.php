<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PartySupplies;

    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter, Filter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class PartySuppiesResource extends Resource
    {
        protected static ?string $model = \App\Domains\PartySupplies\Models\PartySupply::class;
        protected static ?string $navigationIcon = 'heroicon-o-sparkles';
        protected static ?string $navigationGroup = 'PartySupplies';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('supplier')->label('Производитель')->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Фото')->image()->directory('party')->columnSpan(1),
                        FileUpload::make('photos')->label('Галерея')->image()->multiple()->directory('party')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и событие')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'decorations' => '🎊 Декорации',
                                'balloons' => '🎈 Воздушные шары',
                                'tableware' => '🍽️ Посуда',
                                'costumes' => '🎭 Костюмы',
                                'masks' => '🎭 Маски',
                                'confetti' => '✨ Конфетти',
                                'gifts' => '🎁 Подарки',
                                'cakes' => '🎂 Торты',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Select::make('event_type')
                            ->label('Для события')
                            ->options([
                                'birthday' => 'День рождения',
                                'wedding' => 'Свадьба',
                                'corporate' => 'Корпоратив',
                                'new_year' => 'Новый год',
                                'halloween' => 'Хеллоуин',
                                'universal' => 'Универсальное',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TagsInput::make('features')->label('Свойства')->columnSpan('full'),

                        Select::make('color')->label('Цвет')->options(['red' => 'Красный', 'gold' => 'Золото', 'silver' => 'Серебро', 'rainbow' => 'Радуга', 'multicolor' => 'Разноцветный'])->columnSpan(2),

                        TextInput::make('quantity_per_set')->label('В наборе')->numeric()->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и запасы')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('current_stock')->label('На складе')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('min_stock_threshold')->label('Мин. запас')->numeric()->columnSpan(1),
                        Select::make('stock_status')->label('Статус')->options(['in_stock' => '✓ В наличии', 'low_stock' => '⚠️ Мало', 'out_of_stock' => '❌ Нет'])->columnSpan(2),
                    ])->columns(4),

                Section::make('Применение')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('guests_count_from')->label('Гостей от')->numeric()->columnSpan(1),
                        TextInput::make('guests_count_to')->label('Гостей до')->numeric()->columnSpan(1),
                        TextInput::make('age_from')->label('Возраст от')->numeric()->columnSpan(1),
                        TextInput::make('age_to')->label('Возраст до')->numeric()->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextColumn::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextColumn::make('purchase_count')->label('Куплено')->numeric()->disabled()->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('is_eco')->label('♻️ Экологичный')->columnSpan(2),
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
                BadgeColumn::make('category')->label('Категория')->color('info'),
                BadgeColumn::make('event_type')->label('Событие')->color('warning'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                BadgeColumn::make('stock_status')->label('Запас')->color(fn ($state) => $state === 'in_stock' ? 'success' : 'danger'),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                TextColumn::make('purchase_count')->label('Куплено')->numeric(),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')->options(['decorations' => 'Декорации', 'balloons' => 'Воздушные шары'])->multiple(),
                SelectFilter::make('event_type')->label('Событие')->options(['birthday' => 'День рождения', 'wedding' => 'Свадьба'])->multiple(),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_active' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\PartySupplies\PartySuppiesResource\Pages\ListPartySuppies::route('/'),
                'create' => \App\Filament\Tenant\Resources\PartySupplies\PartySuppiesResource\Pages\CreatePartySuppies::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\PartySupplies\PartySuppiesResource\Pages\EditPartySuppies::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

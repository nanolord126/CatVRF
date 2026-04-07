<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Luxury;

    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter, Filter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class LuxuryProductResource extends Resource
    {
        protected static ?string $model = \App\Domains\Luxury\Models\LuxuryProduct::class;
        protected static ?string $navigationIcon = 'heroicon-o-diamond';
        protected static ?string $navigationGroup = 'Luxury';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->columnSpan(1),
                        TextInput::make('reference_code')->label('Артикул')->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('main_photo')->label('Главное фото')->image()->directory('luxury')->columnSpan(1),
                        FileUpload::make('photos')->label('Галерея')->image()->multiple()->directory('luxury')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и происхождение')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('category')
                            ->label('Категория')
                            ->options([
                                'jewelry' => '💎 Ювелирные изделия',
                                'watches' => '⌚ Часы',
                                'bags' => '👜 Сумки',
                                'shoes' => '👠 Обувь',
                                'clothing' => '👗 Одежда',
                                'accessories' => '🕶️ Аксессуары',
                                'fragrances' => '💐 Парфюм',
                                'art' => '🎨 Предметы искусства',
                            ])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('brand')->label('Бренд')->required()->columnSpan(2),

                        Select::make('origin_country')
                            ->label('Страна происхождения')
                            ->options(['france' => 'Франция', 'italy' => 'Италия', 'japan' => 'Япония', 'swiss' => 'Швейцария', 'usa' => 'США'])
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('collection')->label('Коллекция')->columnSpan(2),

                        Select::make('material')->label('Материал')->options(['gold' => 'Золото', 'platinum' => 'Платина', 'silver' => 'Серебро', 'diamond' => 'Алмазы', 'leather' => 'Кожа'])->columnSpan(2),

                        TextInput::make('authenticity_certificate')->label('Номер сертификата подлинности')->columnSpan('full'),
                    ])->columns(4),

                Section::make('Цена и редкость')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        Select::make('price_tier')->label('Ценовой уровень')->options(['ultra_luxury' => 'Ультра-люкс (>500k)', 'luxury' => 'Люкс (100-500k)', 'premium' => 'Премиум (50-100k)'])->columnSpan(2),
                        TextInput::make('current_stock')->label('На складе')->numeric()->disabled()->columnSpan(1),
                        Select::make('limited_edition')->label('Статус')->options(['limited' => 'Ограниченный выпуск', 'exclusive' => 'Эксклюзив', 'standard' => 'Стандарт'])->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('production_year')->label('Год выпуска')->numeric()->columnSpan(1),
                        TextInput::make('condition')->label('Состояние')->options(['new' => 'Новое', 'excellent' => 'Отличное', 'good' => 'Хорошее', 'vintage' => 'Винтаж'])->columnSpan(1),
                        TextInput::make('size')->label('Размер')->columnSpan(1),
                        TextInput::make('serial_number')->label('Серийный номер')->columnSpan(1),
                        TagsInput::make('features')->label('Особенности')->columnSpan('full'),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextInput::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('review_count')->label('Отзывов')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('waitlist_count')->label('В листе ожидания')->numeric()->disabled()->columnSpan(2),
                    ])->columns(4),

                Section::make('Управление')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Toggle::make('is_active')->label('Активен')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('requires_verification')->label('🔍 Требует верификации')->columnSpan(2),
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
                TextColumn::make('brand')->label('Бренд')->searchable()->sortable(),
                BadgeColumn::make('category')->label('Категория')->color('info'),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                BadgeColumn::make('price_tier')->label('Уровень')->color(fn ($state) => $state === 'ultra_luxury' ? 'danger' : 'warning'),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4.5 ? 'success' : 'warning'),
                BadgeColumn::make('limited_edition')->label('Статус')->color('success'),
                BooleanColumn::make('requires_verification')->label('🔍'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')->options(['jewelry' => 'Ювелирные изделия', 'watches' => 'Часы'])->multiple(),
                SelectFilter::make('price_tier')->label('Уровень цены')->options(['ultra_luxury' => 'Ультра-люкс', 'luxury' => 'Люкс'])->multiple(),
                TernaryFilter::make('requires_verification')->label('Требует верификации'),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('verify')->label('Верифицировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['requires_verification' => false])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Luxury\LuxuryProductResource\Pages\ListLuxuryProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\Luxury\LuxuryProductResource\Pages\CreateLuxuryProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Luxury\LuxuryProductResource\Pages\EditLuxuryProduct::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MusicAndInstruments;

    use Filament\Forms\Form;
    use Filament\Resources\Resource;
    use Filament\Tables;
    use Filament\Tables\Columns\{TextColumn, BadgeColumn, BooleanColumn, ImageColumn};
    use Filament\Tables\Filters\{SelectFilter, TernaryFilter, TrashedFilter};
    use Filament\Tables\Actions\{ActionGroup, ViewAction, EditAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class InstrumentResource extends Resource
    {
        protected static ?string $model = \App\Domains\MusicAndInstruments\Models\Instrument::class;
        protected static ?string $navigationIcon = 'heroicon-o-musical-note';
        protected static ?string $navigationGroup = 'MusicAndInstruments';

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
                        FileUpload::make('main_photo')->label('Фото')->image()->directory('instruments')->columnSpan(1),
                        FileUpload::make('photos')->label('Галерея')->image()->multiple()->directory('instruments')->columnSpan(1),
                    ])->columns(4),

                Section::make('Категория и тип')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Select::make('instrument_type')
                            ->label('Тип инструмента')
                            ->options([
                                'strings' => '🎸 Струнные',
                                'wind' => '🎺 Духовые',
                                'percussion' => '🥁 Ударные',
                                'keyboard' => '⌨️ Клавишные',
                                'electronic' => '🎹 Электронные',
                                'folk' => '🪕 Народные',
                            ])
                            ->required()
                            ->columnSpan(2),

                        Select::make('level')->label('Уровень')->options(['beginner' => 'Начинающий', 'intermediate' => 'Средний', 'advanced' => 'Продвинутый', 'professional' => 'Профессиональный'])->required()->columnSpan(2),

                        TextInput::make('material')->label('Материал')->columnSpan(2),

                        TagsInput::make('features')->label('Особенности')->columnSpan('full'),

                        TextInput::make('warranty_months')->label('Гарантия (месяцев)')->numeric()->columnSpan(2),
                    ])->columns(4),

                Section::make('Цена и запасы')
                    ->icon('heroicon-m-banknote')
                    ->schema([
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->suffix('₽')->columnSpan(2),
                        TextInput::make('current_stock')->label('На складе')->numeric()->disabled()->columnSpan(1),
                        TextInput::make('min_stock_threshold')->label('Мин. запас')->numeric()->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('sound_quality')->label('Качество звука')->numeric()->columnSpan(1),
                        TextInput::make('weight_kg')->label('Вес (кг)')->numeric()->columnSpan(1),
                        TextInput::make('dimensions')->label('Размеры')->columnSpan(2),
                        Toggle::make('is_portable')->label('Портативный')->columnSpan(2),
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
                        Toggle::make('is_rental_available')->label('📅 Доступна аренда')->columnSpan(2),
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
                BadgeColumn::make('instrument_type')->label('Тип')->color('info'),
                TextColumn::make('brand')->label('Бренд')->searchable(),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                BadgeColumn::make('level')->label('Уровень')->color(fn ($state) => $state === 'professional' ? 'success' : 'warning'),
                TextColumn::make('rating')->label('⭐')->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('is_rental_available')->label('📅'),
                BooleanColumn::make('is_active')->label('Активен')->toggleable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('instrument_type')->label('Тип')->options(['strings' => 'Струнные', 'wind' => 'Духовые', 'percussion' => 'Ударные'])->multiple(),
                SelectFilter::make('level')->label('Уровень')->options(['beginner' => 'Начинающий', 'professional' => 'Профессиональный'])->multiple(),
                TernaryFilter::make('is_rental_available')->label('Доступна аренда'),
                TrashedFilter::make(),
            ])
            ->actions([ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()])])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->action(fn ($records) => $records->each(fn ($r) => $r->update(['is_active' => true])))->deselectRecordsAfterCompletion()])])
            ->defaultSort('created_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\MusicAndInstruments\InstrumentResource\Pages\ListInstruments::route('/'),
                'create' => \App\Filament\Tenant\Resources\MusicAndInstruments\InstrumentResource\Pages\CreateInstrument::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\MusicAndInstruments\InstrumentResource\Pages\EditInstrument::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

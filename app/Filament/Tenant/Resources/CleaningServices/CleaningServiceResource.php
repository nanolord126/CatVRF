<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CleaningServices;

    use Filament\Resources\Resource;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class CleaningServiceResource extends Resource
    {
        protected static ?string $model = CleaningService::class;
        protected static ?string $navigationIcon = 'heroicon-m-sparkles';
        protected static ?string $navigationGroup = 'Services';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🧹 Услуга уборки')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        Select::make('service_type')->label('Тип уборки')->options(['general' => 'Общая', 'deep' => 'Глубокая', 'window' => 'Окна', 'carpet' => 'Ковры', 'post_repair' => 'После ремонта', 'commercial' => 'Коммерческая'])->required()->columnSpan(1),
                        TextInput::make('price_per_hour')->label('Цена/час (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('min_duration_hours')->label('Мин. длительность (ч)')->numeric()->columnSpan(1),
                        TextInput::make('square_meter_coverage')->label('Производительность (м²/ч)')->numeric()->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('image')->label('Фото')->image()->directory('cleaning')->columnSpan(1),
                    ])->columns(4),

                Section::make('Возможности')
                    ->icon('heroicon-m-check-circle')
                    ->schema([
                        Toggle::make('eco_friendly_products')->label('♻️ Экопродукты')->columnSpan(1),
                        Toggle::make('equipment_included')->label('🧰 Оборудование включено')->columnSpan(1),
                        Toggle::make('insured')->label('保险 Застрахована')->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐ Рекомендуемый')->columnSpan(1),
                        Toggle::make('is_bestseller')->label('🔥 Популярный')->columnSpan(1),
                    ])->columns(3),

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
                TextColumn::make('name')->label('Услуга')->searchable()->sortable()->limit(40),
                BadgeColumn::make('service_type')->label('Тип')->color(fn ($state) => match($state) { 'general' => 'blue', 'deep' => 'purple', 'window' => 'cyan', 'carpet' => 'orange', 'post_repair' => 'red', 'commercial' => 'green' }),
                TextColumn::make('price_per_hour')->label('Цена/ч')->money('RUB', divideBy: 100),
                TextColumn::make('square_meter_coverage')->label('м²/ч')->numeric()->alignment('center'),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('eco_friendly_products')->label('♻️')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('service_type')->label('Тип уборки')->options(['general' => 'Общая', 'deep' => 'Глубокая', 'window' => 'Окна', 'carpet' => 'Ковры'])->multiple(),
                TernaryFilter::make('eco_friendly_products')->label('Экопродукты'),
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
                'index' => \App\Filament\Tenant\Resources\CleaningServices\Pages\ListServices::route('/'),
                'create' => \App\Filament\Tenant\Resources\CleaningServices\Pages\CreateService::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\CleaningServices\Pages\EditService::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

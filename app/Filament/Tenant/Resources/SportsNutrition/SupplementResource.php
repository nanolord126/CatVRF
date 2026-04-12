<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\SportsNutrition;

    use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
    use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
    use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Database\Eloquent\Builder;

    final class SupplementResource extends Resource
    {
        protected static ?string $model = Supplement::class;
        protected static ?string $navigationIcon = 'heroicon-m-bolt';
        protected static ?string $navigationGroup = 'Sports & Fitness';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('💪 Спортивное питание')
                    ->icon('heroicon-m-bolt')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        TextInput::make('brand')->label('Бренд')->required()->columnSpan(1),
                        Select::make('type')->label('Тип')->options([
                            'protein' => 'Протеин',
                            'creatine' => 'Креатин',
                            'bcaa' => 'BCAA',
                            'pre_workout' => 'Пред-тренировка',
                            'multivitamin' => 'Витамины',
                            'fat_burner' => 'Жиросжигатель',
                            'gainer' => 'Гейнер',
                        ])->required()->columnSpan(1),
                        TextInput::make('serving_size')->label('Порция (г)')->numeric()->columnSpan(1),
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('stock')->label('В наличии (шт)')->numeric()->required()->columnSpan(1),
                        Select::make('flavor')->label('Вкус')->options(['vanilla' => 'Ваниль', 'chocolate' => 'Шоколад', 'strawberry' => 'Клубника', 'unflavored' => 'Без вкуса'])->multiple()->columnSpan(2),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('image')->label('Фото')->image()->directory('supplements')->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Toggle::make('is_vegan')->label('🌿 Веган')->columnSpan(1),
                        Toggle::make('is_organic')->label('🌾 Органическое')->columnSpan(1),
                        Toggle::make('is_tested')->label('✓ Тестировано')->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐')->columnSpan(1),
                        Toggle::make('is_bestseller')->label('🔥')->columnSpan(1),
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
                TextColumn::make('name')->label('Продукт')->searchable()->sortable()->icon('heroicon-m-bolt')->limit(40),
                TextColumn::make('brand')->label('Бренд')->searchable(),
                BadgeColumn::make('type')->label('Тип')->color(fn ($state) => match($state) { 'protein' => 'blue', 'creatine' => 'red', 'bcaa' => 'green', 'pre_workout' => 'purple', 'multivitamin' => 'orange', 'fat_burner' => 'yellow', 'gainer' => 'cyan' }),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('stock')->label('Остаток')->numeric()->alignment('center')->badge()->color(fn ($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('is_tested')->label('✓')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('type')->label('Тип')->options([
                    'protein' => 'Протеин',
                    'creatine' => 'Креатин',
                    'bcaa' => 'BCAA',
                    'pre_workout' => 'Пред-тренировка',
                ])->multiple(),
                TernaryFilter::make('is_tested')->label('Протестировано'),
                Filter::make('low_stock')->label('Кончается')->query(fn (Builder $query) => $query->where('stock', '<', 5)),
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
                'index' => \App\Filament\Tenant\Resources\SportsNutrition\Pages\ListSupplements::route('/'),
                'create' => \App\Filament\Tenant\Resources\SportsNutrition\Pages\CreateSupplement::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\SportsNutrition\Pages\EditSupplement::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

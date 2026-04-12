<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ToysAndGames;

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

    final class ToyResource extends Resource
    {
        protected static ?string $model = Toy::class;
        protected static ?string $navigationIcon = 'heroicon-m-cube';
        protected static ?string $navigationGroup = 'Toys & Kids';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🎮 Основная информация')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        Select::make('category')->label('Категория')->options(['action_figures' => 'Фигурки', 'building_blocks' => 'Конструкторы', 'dolls' => 'Куклы', 'puzzles' => 'Пазлы', 'games' => 'Игры', 'educational' => 'Обучающие', 'outdoor' => 'Уличные'])->required()->columnSpan(1),
                        TextInput::make('age_from')->label('Возраст от')->numeric()->columnSpan(1),
                        TextInput::make('age_to')->label('До')->numeric()->columnSpan(1),
                        TextInput::make('price')->label('Цена (₽)')->numeric()->required()->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('image')->label('Фото')->image()->directory('toys')->columnSpan(1),
                    ])->columns(4),

                Section::make('Характеристики')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Toggle::make('is_educational')->label('📚 Обучающая')->columnSpan(1),
                        Toggle::make('is_eco_friendly')->label('♻️ Экологичная')->columnSpan(1),
                        Toggle::make('has_batteries')->label('🔋 С батарейками')->columnSpan(1),
                        Toggle::make('is_safe_certified')->label('✓ Безопасна')->columnSpan(1),
                    ])->columns(4),

                Section::make('Рейтинг и статус')
                    ->icon('heroicon-m-star')
                    ->schema([
                        TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                        Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                        Toggle::make('is_featured')->label('⭐')->columnSpan(1),
                        Toggle::make('is_bestseller')->label('🔥')->columnSpan(1),
                    ])->columns(4),

                Section::make('Служебная информация')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Hidden::make('tenant_id')->default(fn () => tenant('id')),
                        Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                        Hidden::make('business_group_id')->default(fn () => filament()->getTenant()?->active_business_group_id),
                    ])->columns('full'),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->label('Игрушка')->searchable()->sortable()->icon('heroicon-m-cube')->limit(40),
                BadgeColumn::make('category')->label('Категория')->color(fn ($state) => match($state) { 'action_figures' => 'blue', 'building_blocks' => 'orange', 'dolls' => 'pink', 'puzzles' => 'purple', 'games' => 'green', default => 'gray' }),
                TextColumn::make('price')->label('Цена')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('age_from')->label('Возраст')->formatStateUsing(fn ($state, $record) => "{$state}+{$record->age_to ? ' -'.$record->age_to : ''}")->alignment('center'),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('is_educational')->label('📚')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('category')->label('Категория')->options(['action_figures' => 'Фигурки', 'building_blocks' => 'Конструкторы', 'dolls' => 'Куклы', 'puzzles' => 'Пазлы', 'games' => 'Игры'])->multiple(),
                TernaryFilter::make('is_educational')->label('Обучающая'),
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
                'index' => \App\Filament\Tenant\Resources\ToysAndGames\Pages\ListToys::route('/'),
                'create' => \App\Filament\Tenant\Resources\ToysAndGames\Pages\CreateToy::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\ToysAndGames\Pages\EditToy::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

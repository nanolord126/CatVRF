<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\MeatShops;

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

    final class MeatProductResource extends Resource
    {
        protected static ?string $model = MeatProduct::class;
        protected static ?string $navigationIcon = 'heroicon-m-cube';
        protected static ?string $navigationGroup = 'Food & Delivery';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('🍖 Информация о мясе')
                    ->icon('heroicon-m-cube')
                    ->schema([
                        TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                        TextInput::make('name')->label('Название')->required()->columnSpan(2),
                        Select::make('meat_type')->label('Тип мяса')->options([
                            'beef' => 'Говядина',
                            'pork' => 'Свинина',
                            'chicken' => 'Курица',
                            'lamb' => 'Баранина',
                            'game' => 'Дичь',
                        ])->required()->columnSpan(1),
                        Select::make('cut_type')->label('Часть туши')->options([
                            'sirloin' => 'Вырезка',
                            'ribs' => 'Рёбра',
                            'ground' => 'Фарш',
                            'tenderloin' => 'Филе',
                        ])->required()->columnSpan(1),
                        TextInput::make('price')->label('Цена за кг (₽)')->numeric()->required()->columnSpan(1),
                        TextInput::make('available_weight')->label('Вес в наличии (кг)')->numeric()->required()->columnSpan(1),
                        RichEditor::make('description')->label('Описание')->columnSpan('full'),
                        FileUpload::make('image')->label('Фото')->image()->directory('meat')->columnSpan(1),
                    ])->columns(4),

                Section::make('Сертификация')
                    ->icon('heroicon-m-tag')
                    ->schema([
                        Toggle::make('is_organic')->label('🌿 Органическое')->columnSpan(1),
                        Toggle::make('has_gost')->label('📜 ГОСТ')->columnSpan(1),
                        Toggle::make('vacuum_packed')->label('📦 Вакуумная упаковка')->columnSpan(1),
                        Toggle::make('frozen')->label('❄️ Заморожено')->columnSpan(1),
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
                TextColumn::make('name')->label('Продукт')->searchable()->sortable()->icon('heroicon-m-cube')->limit(40),
                BadgeColumn::make('meat_type')->label('Тип')->formatStateUsing(fn ($state) => match($state) { 'beef' => 'Говядина', 'pork' => 'Свинина', 'chicken' => 'Курица', default => 'Прочее' })->color(fn ($state) => match($state) { 'beef' => 'red', 'pork' => 'orange', 'chicken' => 'yellow', default => 'gray' }),
                TextColumn::make('price')->label('Цена/кг')->money('RUB', divideBy: 100)->sortable(),
                TextColumn::make('available_weight')->label('Вес (кг)')->numeric()->alignment('center'),
                TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
                BooleanColumn::make('is_organic')->label('🌿')->toggleable(),
                BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
                BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
            ])->filters([
                SelectFilter::make('meat_type')->label('Тип мяса')->options(['beef' => 'Говядина', 'pork' => 'Свинина', 'chicken' => 'Курица', 'lamb' => 'Баранина'])->multiple(),
                TernaryFilter::make('is_organic')->label('Органическое'),
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
                'index' => \App\Filament\Tenant\Resources\MeatShops\Pages\ListProducts::route('/'),
                'create' => \App\Filament\Tenant\Resources\MeatShops\Pages\CreateProduct::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\MeatShops\Pages\EditProduct::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

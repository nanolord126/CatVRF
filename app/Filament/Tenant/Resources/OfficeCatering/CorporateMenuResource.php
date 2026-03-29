<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\OfficeCatering;

use App\Domains\OfficeCatering\Models\CorporateMenu;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\Hidden, Components\RichEditor, Components\FileUpload};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, ActionGroup};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

final class CorporateMenuResource extends Resource
{
    protected static ?string $model = CorporateMenu::class;
    protected static ?string $navigationIcon = 'heroicon-m-cube';
    protected static ?string $navigationGroup = 'Food & Delivery';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('🏢 Корпоративное меню')
                ->icon('heroicon-m-cube')
                ->schema([
                    TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                    TextInput::make('name')->label('Название меню')->required()->columnSpan(2),
                    Select::make('menu_type')->label('Тип')->options(['breakfast' => 'Завтрак', 'lunch' => 'Обед', 'dinner' => 'Ужин', 'snacks' => 'Перекусы', 'combined' => 'Полный день'])->required()->columnSpan(1),
                    TextInput::make('servings')->label('Порций')->numeric()->required()->columnSpan(1),
                    TextInput::make('price_per_serving')->label('Цена на персону (₽)')->numeric()->required()->columnSpan(1),
                    Select::make('diet_type')->label('Диета')->options(['mixed' => 'Общее', 'vegetarian' => 'Вегетарианское', 'vegan' => 'Веган', 'keto' => 'Кето'])->columnSpan(1),
                    RichEditor::make('description')->label('Описание')->columnSpan('full'),
                    FileUpload::make('image')->label('Фото')->image()->directory('catering')->columnSpan(1),
                ])->columns(4),

            Section::make('Параметры')
                ->icon('heroicon-m-check-circle')
                ->schema([
                    Toggle::make('has_hot_dishes')->label('🔥 Горячие блюда')->columnSpan(1),
                    Toggle::make('has_desserts')->label('🍰 Десерты')->columnSpan(1),
                    Toggle::make('has_drinks')->label('☕ Напитки')->columnSpan(1),
                    Toggle::make('can_customize')->label('📝 Кастомизация')->columnSpan(1),
                ])->columns(4),

            Section::make('Статус')
                ->icon('heroicon-m-star')
                ->schema([
                    Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                    Toggle::make('is_featured')->label('⭐ Популярный')->columnSpan(1),
                    Toggle::make('is_bestseller')->label('🔥 Бестселлер')->columnSpan(1),
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
            TextColumn::make('name')->label('Меню')->searchable()->sortable()->limit(40),
            BadgeColumn::make('menu_type')->label('Тип')->color(fn ($state) => match($state) { 'breakfast' => 'yellow', 'lunch' => 'blue', 'dinner' => 'purple', 'snacks' => 'orange', 'combined' => 'green' }),
            TextColumn::make('servings')->label('Порций')->numeric()->alignment('center'),
            TextColumn::make('price_per_serving')->label('Цена (₽)')->money('RUB', divideBy: 100)->sortable(),
            BadgeColumn::make('diet_type')->label('Диета')->color(fn ($state) => match($state) { 'vegetarian' => 'green', 'vegan' => 'cyan', 'keto' => 'orange', default => 'blue' }),
            BooleanColumn::make('has_hot_dishes')->label('🔥')->toggleable(),
            BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
            BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
        ])->filters([
            SelectFilter::make('menu_type')->label('Тип')->options(['breakfast' => 'Завтрак', 'lunch' => 'Обед', 'dinner' => 'Ужин'])->multiple(),
            SelectFilter::make('diet_type')->label('Диета')->options(['vegetarian' => 'Вегетарианское', 'vegan' => 'Веган', 'keto' => 'Кето'])->multiple(),
            TernaryFilter::make('can_customize')->label('С кастомизацией'),
            TrashedFilter::make(),
        ])->actions([
            ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]),
        ])->bulkActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\OfficeCatering\Pages\ListMenus::route('/'),
            'create' => \App\Filament\Tenant\Resources\OfficeCatering\Pages\CreateMenu::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\OfficeCatering\Pages\EditMenu::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }
}

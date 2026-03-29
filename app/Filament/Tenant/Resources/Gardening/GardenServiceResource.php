<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Gardening;

use App\Domains\Gardening\Models\GardenService;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\TagsInput, Components\Hidden, Components\RichEditor, Components\FileUpload};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter, Filters\Filter};
use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

final class GardenServiceResource extends Resource
{
    protected static ?string $model = GardenService::class;
    protected static ?string $navigationIcon = 'heroicon-m-leaf';
    protected static ?string $navigationGroup = 'Gardening & Landscape';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('🌿 Услуга озеленения')
                ->icon('heroicon-m-leaf')
                ->schema([
                    TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                    TextInput::make('name')->label('Название')->required()->columnSpan(2),
                    TextInput::make('price_per_hour')->label('Цена/час (₽)')->numeric()->required()->columnSpan(1),
                    TextInput::make('min_order_size')->label('Мин. работ (м²)')->numeric()->columnSpan(1),
                    RichEditor::make('description')->label('Описание')->columnSpan('full'),
                    FileUpload::make('service_photo')->label('Фото услуги')->image()->directory('gardening')->columnSpan(1),
                ])->columns(4),

            Section::make('Категория')
                ->icon('heroicon-m-tag')
                ->schema([
                    Select::make('service_category')->label('Категория')->options([
                        'landscaping' => 'Ландшафтный дизайн',
                        'lawn_care' => 'Уход за газоном',
                        'pruning' => 'Обрезка растений',
                        'planting' => 'Посадка деревьев',
                        'pest_control' => 'Обработка вредителей',
                    ])->required()->columnSpan(2),
                    TagsInput::make('plants_expertise')->label('Специализация')->columnSpan(2),
                ])->columns(4),

            Section::make('Рейтинг и статус')
                ->icon('heroicon-m-star')
                ->schema([
                    TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                    Toggle::make('is_active')->label('Активная')->default(true)->columnSpan(1),
                    Toggle::make('is_featured')->label('⭐ Рекомендуемая')->columnSpan(1),
                    Toggle::make('is_certified')->label('📜 Сертифицирована')->columnSpan(1),
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
            TextColumn::make('name')->label('Услуга')->searchable()->sortable()->icon('heroicon-m-leaf')->limit(40),
            BadgeColumn::make('service_category')->label('Категория')->color(fn ($state) => match($state) { 'landscaping' => 'green', 'lawn_care' => 'cyan', 'pruning' => 'blue', default => 'gray' }),
            TextColumn::make('price_per_hour')->label('Цена/час')->money('RUB', divideBy: 100)->sortable(),
            TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4 ? 'success' : 'warning'),
            BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
            BooleanColumn::make('is_certified')->label('📜')->toggleable(),
            BooleanColumn::make('is_active')->label('Активная')->toggleable()->sortable(),
        ])->filters([
            SelectFilter::make('service_category')->label('Категория')->options(['landscaping' => 'Ландшафтный дизайн', 'lawn_care' => 'Уход за газоном', 'pruning' => 'Обрезка'])->multiple(),
            TernaryFilter::make('is_certified')->label('Сертифицирована'),
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
            'index' => \App\Filament\Tenant\Resources\Gardening\Pages\ListServices::route('/'),
            'create' => \App\Filament\Tenant\Resources\Gardening\Pages\CreateService::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\Gardening\Pages\EditService::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }
}

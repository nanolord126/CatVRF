<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Domain\Enums\ServiceCategory;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautySalon;
use App\Domains\Beauty\Infrastructure\Persistence\Eloquent\Models\BeautyService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Service Resource (Layer 7)
 *
 * Управление услугами салона красоты.
 * Полностью соответствует канону от 30.03.2026.
 *
 * @version 1.1
 */
final class ServiceResource extends Resource
{
    protected static ?string $model = BeautyService::class;

    protected static ?string $navigationIcon   = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup  = 'Beauty';
    protected static ?string $navigationLabel  = 'Услуги';
    protected static ?string $modelLabel       = 'Услуга';
    protected static ?string $pluralModelLabel = 'Услуги';
    protected static ?int    $navigationSort   = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->schema([
                    Select::make('salon_id')
                        ->label('Салон')
                        ->options(fn () => BeautySalon::query()->where('tenant_id', filament()->getTenant()?->id)->where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->columnSpan(2),
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('Название услуги'),
                    Select::make('category')
                        ->label('Категория')
                        ->options(fn () => collect(ServiceCategory::cases())->mapWithKeys(fn (ServiceCategory $c) => [$c->value => $c->label()]))
                        ->required()
                        ->searchable(),
                ])->columns(2),

            Forms\Components\Section::make('Детали и цена')
                ->schema([
                    Forms\Components\RichEditor::make('description')
                        ->columnSpanFull()
                        ->label('Описание'),
                    Forms\Components\TextInput::make('duration_minutes')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->label('Длительность (минуты)'),
                    Forms\Components\TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->step(1)
                        ->label('Цена (копейки)'),
                ])->columns(2),

            Forms\Components\Section::make('Расходники и теги')
                ->schema([
                    Forms\Components\KeyValue::make('consumables_json')
                        ->label('Расходные материалы (JSON)')
                        ->helperText('Пример: {"gloves": 2, "paint_ml": 50}'),
                    Forms\Components\TagsInput::make('tags')
                        ->label('Теги'),
                ])->columns(2),

            Forms\Components\Section::make('Статус')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true),
                ]),

            Forms\Components\Hidden::make('uuid')->default(fn () => Str::uuid()->toString())->dehydrated(),
            Forms\Components\Hidden::make('tenant_id')->default(fn () => filament()->getTenant()?->id),
            Forms\Components\Hidden::make('correlation_id')->default(fn () => Str::uuid()->toString()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable()->label('Название'),
            Tables\Columns\TextColumn::make('salon.name')->searchable()->label('Салон'),
            Tables\Columns\TextColumn::make('master.full_name')->searchable()->label('Мастер'),
            Tables\Columns\TextColumn::make('category')->label('Категория'),
            Tables\Columns\TextColumn::make('price')->money('RUB', divideBy: 100)->sortable()->label('Цена'),
            Tables\Columns\TextColumn::make('duration_minutes')->numeric()->label('Длительность'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Активна'),
            Tables\Columns\TextColumn::make('rating')->numeric()->sortable()->label('Рейтинг'),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('salon_id')
                ->options(fn () => BeautySalon::query()->where('tenant_id', filament()->getTenant()?->id)->pluck('name', 'id'))
                ->label('Салон'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()?->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages;
use App\Services\FraudControlService;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Beauty\Models\Service;

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
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Beauty & Wellness';
    protected static ?string $navigationLabel = 'Услуги';
    protected static ?string $modelLabel = 'услугу';
    protected static ?string $pluralModelLabel = 'услуги';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->schema([
                    Forms\Components\Select::make('salon_id')
                        ->relationship('salon', 'name', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                        ->required()
                        ->label('Салон'),
                    Forms\Components\Select::make('master_id')
                        ->relationship('master', 'full_name', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                        ->required()
                        ->label('Мастер'),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('Название услуги'),
                    Forms\Components\TextInput::make('category')
                        ->required()
                        ->label('Категория'),
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

            Forms\Components\Hidden::make('uuid')->default(fn () => (string) Str::uuid())->dehydrated(),
            Forms\Components\Hidden::make('tenant_id')->default(fn () => tenant('id')),
            Forms\Components\Hidden::make('correlation_id')->default(fn () => (string) Str::uuid()),
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
                ->relationship('salon', 'name', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                ->label('Салон'),
            Tables\Filters\SelectFilter::make('master_id')
                ->relationship('master', 'full_name', fn(Builder $query) => $query->where('tenant_id', tenant('id')))
                ->label('Мастер'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()->action(function ($record) {
                DB::transaction(function () use ($record) {
                    $correlationId = (string) Str::uuid();
                    app(FraudControlService::class)->check(Auth::id(), 'delete-service', $record->price, $correlationId);
                    Log::channel('audit')->info('Service deleted', ['record_id' => $record->id, 'tenant_id' => tenant('id'), 'correlation_id' => $correlationId]);
                    $record->delete();
                });
            }),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make()->action(function ($records) {
                    DB::transaction(function () use ($records) {
                        $correlationId = (string) Str::uuid();
                        app(FraudControlService::class)->check(Auth::id(), 'bulk-delete-services', 0, $correlationId);
                        Log::channel('audit')->info('Services bulk deleted', ['record_ids' => $records->pluck('id')->toArray(), 'tenant_id' => tenant('id'), 'correlation_id' => $correlationId]);
                        $records->each->delete();
                    });
                }),
            ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
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

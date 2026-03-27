<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Fashion\Models\FashionStore;
use App\Services\FraudControlService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FashionStoreResource
 * 
 * Управление магазинами одежды (B2B/B2C).
 * Реализует канон 2026: tenant scoping, correlation_id, glassmorphism UI.
 */
final class FashionStoreResource extends Resource
{
    protected static ?string $model = FashionStore::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Fashion & Style';

    protected static ?string $modelLabel = 'Магазин одежды';

    protected static ?string $pluralModelLabel = 'Магазины одежды';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->description('Базовые данные магазина и юридическая информация')
                    ->aside()
                    ->schema([
                        Forms\Components\Hidden::make('correlation_id')
                            ->default(fn () => (string) Str::uuid()),

                        Forms\Components\TextInput::make('name')
                            ->label('Название магазина')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Напр. Fashion Elite'),

                        Forms\Components\TextInput::make('inn')
                            ->label('ИНН организации')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->length(10)
                            ->placeholder('10 цифр для ЮЛ'),

                        Forms\Components\Select::make('type')
                            ->label('Тип площадки')
                            ->options([
                                'b2c' => 'B2C (Розничный маркетплейс)',
                                'b2b' => 'B2B (Оптовые поставки)',
                                'hybrid' => 'Гибридный (B2C + B2B)',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->onColor('success'),
                    ]),

                Forms\Components\Section::make('Локация и контакты')
                    ->aside()
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Юридический адрес')
                            ->required(),

                        Forms\Components\KeyValue::make('contact_info')
                            ->label('Контактные данные')
                            ->keyLabel('Тип (Phone, Email, TG)')
                            ->valueLabel('Значение')
                            ->addActionLabel('Добавить контакт'),
                    ]),

                Forms\Components\Section::make('Аналитика и Теги')
                    ->aside()
                    ->schema([
                        Forms\Components\TagsInput::make('tags')
                            ->label('Теги для поиска и AI')
                            ->placeholder('premium, kids, sports'),
                        
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Дата создания')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? 'Новый'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН')
                    ->copyable()
                    ->fontFamily('mono'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'primary' => 'b2c',
                        'success' => 'b2b',
                        'warning' => 'hybrid',
                    ]),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Статус')
                    ->boolean(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->numeric(1)
                    ->icon('heroicon-s-star')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'b2c' => 'B2C',
                        'b2b' => 'B2B',
                        'hybrid' => 'Hybrid',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Только активные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->before(function ($record, array $data) {
                        $correlationId = $data['correlation_id'] ?? (string) Str::uuid();
                        Log::channel('audit')->info('FashionStore edit initiated', [
                            'store_id' => $record->id,
                            'correlation_id' => $correlationId,
                        ]);
                        FraudControlService::check(['action' => 'store_update', 'id' => $record->id]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\FashionStoreResource\Pages\ListFashionStores::route('/'),
            'create' => \App\Filament\Tenant\Resources\FashionStoreResource\Pages\CreateFashionStore::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\FashionStoreResource\Pages\EditFashionStore::route('/{record}/edit'),
        ];
    }
}

<?php
declare(strict_types=1);

namespace App\Domains\Art\Filament\Tenant\Resources;


use Psr\Log\LoggerInterface;
use App\Domains\Art\Models\Artwork;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ArtworkResource extends Resource
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    protected static ?string $model = Artwork::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основные данные')
                ->description('B2C/B2B адаптация + строгая валидация')
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255),
                    Forms\Components\Textarea::make('description')->label('Описание')->rows(3)->columnSpanFull(),
                    Forms\Components\TextInput::make('price_cents')->label('Цена, копейки')->numeric()->minValue(0)->required(),
                    Forms\Components\Select::make('artist_id')->label('Художник')->relationship('artist', 'name')->required()->searchable(),
                    Forms\Components\Select::make('project_id')->label('Проект')->relationship('project', 'title')->searchable(),
                    Forms\Components\ToggleButtons::make('meta.sales_mode')
                        ->label('Целевая аудитория')
                        ->options(['b2c' => 'B2C', 'b2b' => 'B2B'])
                        ->default('b2c')
                        ->inline(),
                    Forms\Components\Toggle::make('is_visible')->label('Показывать')->default(true),
                    Forms\Components\DateTimePicker::make('delivered_at')->label('Дата сдачи'),
                ])->columns(3),
            Forms\Components\Section::make('Коммерция и лицензия')
                ->schema([
                    Forms\Components\TextInput::make('meta.license_type')->label('Тип лицензии')->placeholder('exclusive / non-exclusive'),
                    Forms\Components\TextInput::make('meta.usage_scope')->label('Область использования')->maxLength(255),
                    Forms\Components\TextInput::make('meta.commission_percent')->label('Комиссия, %')->numeric()->minValue(0)->maxValue(30)->default(14),
                    Forms\Components\Toggle::make('meta.allow_derivatives')->label('Разрешить производные')->default(false),
                    Forms\Components\TextInput::make('meta.idempotency_key')->label('Idempotency Key')->maxLength(64),
                    Forms\Components\KeyValue::make('meta.delivery_requirements')->label('Требования к сдаче')->keyLabel('Поле')->valueLabel('Значение')->columnSpanFull(),
                ])->columns(3),
            Forms\Components\Section::make('Логистика и контроль')
                ->schema([
                    Forms\Components\TextInput::make('business_group_id')->label('Business Group ID')->numeric()->helperText('Tenant-aware scoping'),
                    Forms\Components\TextInput::make('tenant_id')->label('Tenant ID')->numeric()->required(),
                    Forms\Components\TextInput::make('meta.storage_location')->label('Хранение')->maxLength(255),
                    Forms\Components\Textarea::make('meta.quality_notes')->label('Заметки QA')->rows(3)->columnSpanFull(),
                    Forms\Components\Repeater::make('meta.dimensions')
                        ->label('Габариты / материалы')
                        ->schema([
                            Forms\Components\TextInput::make('width')->label('Ширина см')->numeric(),
                            Forms\Components\TextInput::make('height')->label('Высота см')->numeric(),
                            Forms\Components\TextInput::make('material')->label('Материал'),
                        ])
                        ->default([])
                        ->collapsible()
                        ->columnSpanFull(),
                ])->columns(3),
            Forms\Components\Section::make('Атрибуты и идентификаторы')
                ->schema([
                    Forms\Components\KeyValue::make('tags')->label('Теги')->keyLabel('Ключ')->valueLabel('Значение')->columnSpanFull(),
                    Forms\Components\KeyValue::make('meta.aux')->label('Доп. мета')->keyLabel('Поле')->valueLabel('Значение')->columnSpanFull(),
                    Forms\Components\TextInput::make('uuid')->label('UUID')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('correlation_id')->label('Correlation ID')->disabled()->dehydrated(false),
                    Forms\Components\Placeholder::make('canon_hint')->label('Канон 2026')->content('fraud-check + audit-log + correlation_id обязательны'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Название')->searchable()->sortable()->limit(40),
            TextColumn::make('artist.name')->label('Художник')->sortable()->toggleable(),
            TextColumn::make('project.title')->label('Проект')->sortable()->toggleable(),
            TextColumn::make('price_cents')->label('Цена, коп')->money('RUB', divideBy: 100)->sortable(),
            TextColumn::make('meta.sales_mode')
                ->label('B2C/B2B')
                ->state(fn (Artwork $record) => data_get($record->meta, 'sales_mode', 'b2c'))
                ->badge(),
            TextColumn::make('meta.license_type')
                ->label('Лицензия')
                ->state(fn (Artwork $record) => data_get($record->meta, 'license_type', '—'))
                ->toggleable(),
            TextColumn::make('is_visible')->label('Видимость')->badge()->formatStateUsing(fn ($state) => $state ? 'Показ' : 'Скрыт'),
            TextColumn::make('delivered_at')->label('Сдано')->since()->toggleable(),
            TextColumn::make('business_group_id')->label('BG')->toggleable(),
            TextColumn::make('tenant_id')->label('Tenant')->toggleable(),
            TextColumn::make('tags')->label('Теги')->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_keys($state)) : '—')->limit(30)->toggleable(),
            TextColumn::make('meta.dimensions')
                ->label('Размеры')
                ->state(fn (Artwork $record) => collect(data_get($record->meta, 'dimensions', []))->map(fn ($item) => trim(($item['width'] ?? '') . 'x' . ($item['height'] ?? '')))->filter()->implode('; '))
                ->toggleable(),
            TextColumn::make('correlation_id')->label('Correlation')->copyable(),
            TextColumn::make('created_at')->label('Создан')->dateTime()->sortable(),
            TextColumn::make('updated_at')->label('Обновлён')->dateTime()->sortable(),
        ])
            ->filters([
                Tables\Filters\Filter::make('visible')->label('Только видимые')->query(fn (Builder $query) => $query->where('is_visible', true)),
                Tables\Filters\Filter::make('delivered')->label('Сданные')->query(fn (Builder $query) => $query->whereNotNull('delivered_at')),
                Tables\Filters\Filter::make('premium')
                    ->label('Цена > 50 000 ₽')
                    ->query(fn (Builder $query) => $query->where('price_cents', '>', 50_000_00)),
                Tables\Filters\Filter::make('b2b')
                    ->label('B2B продажи')
                    ->query(fn (Builder $query) => $query->where('meta->sales_mode', 'b2b')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_visibility')
                    ->label('Сменить видимость')
                    ->icon('heroicon-o-eye')
                    ->requiresConfirmation()
                    ->action(function (Artwork $record): void {
                        $correlationId = (string) Str::uuid();
                        $this->db->transaction(static function () use ($record, $correlationId): void {
                            $record->update([
                                'is_visible' => !$record->is_visible,
                                'correlation_id' => $record->correlation_id ?: $correlationId,
                            ]);
                        });

                        $this->logger->info('Artwork visibility toggled from Filament', [
                            'artwork_id' => $record->id,
                            'new_state' => $record->is_visible,
                            'correlation_id' => $record->correlation_id ?: $correlationId,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (function_exists('tenant') && tenant()) {
            $query->where('tenant_id', (int) tenant()->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArtworks::route('/'),
            'create' => CreateArtwork::route('/create'),
            'edit' => EditArtwork::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();
        $data['tenant_id'] = $data['tenant_id'] ?? (function_exists('tenant') && tenant() ? (int) tenant()->id : 0);

        return $data;
    }
}

final class ListArtworks extends ListRecords
{
    protected static string $resource = ArtworkResource::class;
}

final class CreateArtwork extends CreateRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ArtworkResource::mutateFormDataBeforeCreate($data);
    }
}

final class EditArtwork extends EditRecord
{
    protected static string $resource = ArtworkResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

        return $data;
    }
}

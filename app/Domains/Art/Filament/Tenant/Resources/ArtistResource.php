<?php
declare(strict_types=1);

namespace App\Domains\Art\Filament\Tenant\Resources;

use App\Domains\Art\Models\Artist;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ArtistResource extends Resource
{
    protected static ?string $model = Artist::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Профиль художника')
                ->description('Полное досье по требованиям канона 2026')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('slug')
                        ->label('Слаг')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\Textarea::make('bio')
                        ->label('Био')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('style')
                        ->label('Стиль')
                        ->helperText('Например: сюрреализм, арт-деко, минимализм'),
                    Forms\Components\TextInput::make('rating')
                        ->numeric()
                        ->default(4.8)
                        ->minValue(0)
                        ->maxValue(5),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                    Forms\Components\TextInput::make('business_group_id')
                        ->label('Business Group ID')
                        ->numeric()
                        ->helperText('Tenant-aware scoping для филиалов'),
                    Forms\Components\TextInput::make('tenant_id')
                        ->label('Tenant ID')
                        ->numeric()
                        ->required(),
                    Forms\Components\KeyValue::make('tags')
                        ->label('Теги')
                        ->keyLabel('Ключ')
                        ->valueLabel('Значение'),
                    Forms\Components\KeyValue::make('meta')
                        ->label('Мета')
                        ->keyLabel('Поле')
                        ->valueLabel('Значение')
                        ->helperText('Дополнительные атрибуты художника'),
                    Forms\Components\Placeholder::make('tenant_context')
                        ->label('Tenant / Business Group')
                        ->content(static function (?Artist $record): string {
                            return sprintf(
                                'Tenant: %s | Business Group: %s',
                                $record?->tenant_id ?? 'n/a',
                                $record?->business_group_id ?? 'n/a'
                            );
                        }),
                ])->columns(2),
            Forms\Components\Section::make('Коммуникации и соцсети')
                ->schema([
                    Forms\Components\TextInput::make('meta.email')->label('Email')->email(),
                    Forms\Components\TextInput::make('meta.phone')->label('Телефон')->tel(),
                    Forms\Components\Repeater::make('meta.social_links')
                        ->label('Соцсети')
                        ->schema([
                            Forms\Components\TextInput::make('platform')->label('Платформа'),
                            Forms\Components\TextInput::make('url')->label('URL')->url(),
                        ])
                        ->default([])
                        ->collapsible()
                        ->orderable()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('meta.audience_notes')->label('B2C/B2B заметки')->rows(2)->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Качество и контроль')
                ->schema([
                    Forms\Components\TextInput::make('meta.commission_percent')->label('Комиссия, %')->numeric()->minValue(0)->maxValue(30)->default(14),
                    Forms\Components\Toggle::make('meta.requires_fraud_check')->label('Fraud check обязателен')->default(true),
                    Forms\Components\TextInput::make('meta.rating_count')->label('Количество отзывов')->numeric()->minValue(0)->default(0),
                    Forms\Components\Textarea::make('meta.audit_notes')->label('Аудит-заметки')->rows(3)->columnSpanFull(),
                ])->columns(3),
            Forms\Components\Section::make('Идентификаторы')
                ->schema([
                    Forms\Components\TextInput::make('uuid')
                        ->label('UUID')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('correlation_id')
                        ->label('Correlation ID')
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\Placeholder::make('canon_hint')
                        ->label('Канон 2026')
                        ->content('Correlation_id + audit-log + FraudControlService::check()'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Имя')->searchable()->sortable(),
            TextColumn::make('slug')->label('Слаг')->limit(20)->toggleable(),
            TextColumn::make('style')->label('Стиль')->sortable(),
            TextColumn::make('rating')->label('Рейтинг')->sortable()->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
            TextColumn::make('is_active')->label('Статус')->badge()->formatStateUsing(fn (bool $state) => $state ? 'Активен' : 'Пауза'),
            TextColumn::make('projects_count')->counts('projects')->label('Проекты'),
            TextColumn::make('artworks_count')->counts('artworks')->label('Работы'),
            TextColumn::make('business_group_id')->label('BG')->toggleable(),
            TextColumn::make('tenant_id')->label('Tenant')->toggleable(),
            TextColumn::make('tags')->label('Теги')->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_keys($state)) : '—')->limit(30)->toggleable(),
            TextColumn::make('meta.rating_count')->label('Отзывов')->state(fn (Artist $record) => data_get($record->meta, 'rating_count', 0))->toggleable(),
            TextColumn::make('correlation_id')->label('Correlation')->copyable(),
            TextColumn::make('created_at')->label('Создан')->dateTime(),
            TextColumn::make('updated_at')->label('Обновлён')->dateTime(),
        ])
            ->filters([
                Tables\Filters\Filter::make('active_only')
                    ->label('Только активные')
                    ->query(fn (Builder $query) => $query->where('is_active', true)),
                Tables\Filters\Filter::make('high_rating')
                    ->label('Рейтинг ≥ 4.5')
                    ->query(fn (Builder $query) => $query->where('rating', '>=', 4.5)),
                Tables\Filters\Filter::make('has_projects')
                    ->label('Есть проекты')
                    ->query(fn (Builder $query) => $query->has('projects')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label('Вкл/Выкл')
                    ->icon('heroicon-o-power')
                    ->requiresConfirmation()
                    ->action(function (Artist $record): void {
                        $correlationId = (string) Str::uuid();
                        DB::transaction(static function () use ($record, $correlationId): void {
                            $record->update([
                                'is_active' => !$record->is_active,
                                'correlation_id' => $record->correlation_id ?: $correlationId,
                            ]);
                        });

                        Log::channel('audit')->info('Artist toggled activity from Filament', [
                            'artist_id' => $record->id,
                            'new_state' => $record->is_active,
                            'correlation_id' => $record->correlation_id ?: $correlationId,
                        ]);
                    }),
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
        $query = parent::getEloquentQuery();

        if (function_exists('tenant') && tenant()) {
            $query->where('tenant_id', (int) tenant()->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArtists::route('/'),
            'create' => CreateArtist::route('/create'),
            'edit' => EditArtist::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['tenant_id'] = $data['tenant_id'] ?? (function_exists('tenant') && tenant() ? (int) tenant()->id : 0);

        return $data;
    }
}

final class ListArtists extends ListRecords
{
    protected static string $resource = ArtistResource::class;
}

final class CreateArtist extends CreateRecord
{
    protected static string $resource = ArtistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ArtistResource::mutateFormDataBeforeCreate($data);
    }
}

final class EditArtist extends EditRecord
{
    protected static string $resource = ArtistResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

        return $data;
    }
}

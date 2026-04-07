<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Domains\RealEstate\Domain\Enums\ViewingStatusEnum;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\ViewingAppointmentModel;
use App\Filament\Tenant\Resources\RealEstate\ViewingResource\Pages\ListViewings;
use App\Filament\Tenant\Resources\RealEstate\ViewingResource\Pages\ViewViewing;
use App\Filament\Tenant\Resources\RealEstate\ViewingResource\Pages\EditViewing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ViewingResource extends Resource
{
    protected static ?string $model = ViewingAppointmentModel::class;

    protected static ?string $navigationIcon   = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup  = 'Недвижимость';
    protected static ?string $navigationLabel  = 'Показы';
    protected static ?string $modelLabel       = 'Показ объекта';
    protected static ?string $pluralModelLabel = 'Показы объектов';
    protected static ?int    $navigationSort   = 20;

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Запись на показ')
                ->icon('heroicon-m-calendar-days')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('client_name')
                        ->label('Имя клиента')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('client_phone')
                        ->label('Телефон клиента')
                        ->tel()
                        ->required()
                        ->maxLength(30),

                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Дата и время показа')
                        ->required()
                        ->native(false)
                        ->displayFormat('d.m.Y H:i'),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options(ViewingStatusEnum::options())
                        ->required()
                        ->native(false),

                    Forms\Components\Textarea::make('notes')
                        ->label('Примечания')
                        ->rows(3)
                        ->columnSpan(2),

                    Forms\Components\Textarea::make('cancellation_reason')
                        ->label('Причина отмены')
                        ->rows(2)
                        ->columnSpan(2)
                        ->visible(static fn (Forms\Get $get): bool =>
                            $get('status') === ViewingStatusEnum::Cancelled->value
                        ),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_name')
                    ->label('Клиент')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_phone')
                    ->label('Телефон')
                    ->copyable(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Дата показа')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(static fn (string $state): string => ViewingStatusEnum::from($state)->color())
                    ->formatStateUsing(static fn (string $state): string => ViewingStatusEnum::from($state)->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Объект')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Примечание')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(ViewingStatusEnum::options()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }

    // ── Query ─────────────────────────────────────────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $tenantId = filament()->getTenant()?->id;

        return parent::getEloquentQuery()
            ->withoutGlobalScope('tenant')
            ->when($tenantId !== null, static fn (Builder $q) => $q->where('tenant_id', $tenantId))
            ->with(['property', 'agent']);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index' => ListViewings::route('/'),
            'view'  => ViewViewing::route('/{record}'),
            'edit'  => EditViewing::route('/{record}/edit'),
        ];
    }
}

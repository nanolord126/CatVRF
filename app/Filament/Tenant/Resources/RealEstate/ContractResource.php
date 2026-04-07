<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Domains\RealEstate\Domain\Enums\ContractTypeEnum;
use App\Domains\RealEstate\Infrastructure\Eloquent\Models\ContractModel;
use App\Filament\Tenant\Resources\RealEstate\ContractResource\Pages\CreateContract;
use App\Filament\Tenant\Resources\RealEstate\ContractResource\Pages\EditContract;
use App\Filament\Tenant\Resources\RealEstate\ContractResource\Pages\ListContracts;
use App\Filament\Tenant\Resources\RealEstate\ContractResource\Pages\ViewContract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ContractResource extends Resource
{
    protected static ?string $model = ContractModel::class;

    protected static ?string $navigationIcon   = 'heroicon-o-document-text';
    protected static ?string $navigationGroup  = 'Недвижимость';
    protected static ?string $navigationLabel  = 'Договоры';
    protected static ?string $modelLabel       = 'Договор';
    protected static ?string $pluralModelLabel = 'Договоры';
    protected static ?int    $navigationSort   = 30;

    // ── Form ──────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Договор')
                ->icon('heroicon-m-document-text')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Тип договора')
                        ->options(ContractTypeEnum::options())
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'draft'      => 'Черновик',
                            'signed'     => 'Подписан',
                            'terminated' => 'Расторгнут',
                        ])
                        ->required()
                        ->native(false),

                    Forms\Components\TextInput::make('price_kopecks')
                        ->label('Сумма сделки (руб)')
                        ->numeric()
                        ->required()
                        ->minValue(0)
                        ->formatStateUsing(static fn (?int $state): ?float => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(static fn (?float $state): ?int => $state !== null ? (int) round($state * 100) : null)
                        ->prefix('₽'),

                    Forms\Components\TextInput::make('commission_kopecks')
                        ->label('Комиссия (руб)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(static fn (?int $state): ?float => $state !== null ? $state / 100 : null)
                        ->prefix('₽')
                        ->visibleOn('view'),

                    Forms\Components\TextInput::make('lease_duration_months')
                        ->label('Срок аренды (мес.)')
                        ->numeric()
                        ->minValue(1)
                        ->visible(static fn (Forms\Get $get): bool =>
                            $get('type') === ContractTypeEnum::Rental->value
                        ),

                    Forms\Components\TextInput::make('document_url')
                        ->label('Ссылка на документ')
                        ->url()
                        ->maxLength(1000)
                        ->columnSpan(2),

                    Forms\Components\DateTimePicker::make('signed_at')
                        ->label('Дата подписания')
                        ->native(false)
                        ->displayFormat('d.m.Y H:i'),

                    Forms\Components\DateTimePicker::make('terminated_at')
                        ->label('Дата расторжения')
                        ->native(false)
                        ->displayFormat('d.m.Y H:i'),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => ContractTypeEnum::from($state)->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(static fn (string $state): string => match ($state) {
                        'signed'     => 'success',
                        'terminated' => 'danger',
                        default      => 'gray',
                    })
                    ->formatStateUsing(static fn (string $state): string => match ($state) {
                        'signed'     => 'Подписан',
                        'terminated' => 'Расторгнут',
                        default      => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_kopecks')
                    ->label('Сумма сделки')
                    ->formatStateUsing(static fn (int $state): string => number_format($state / 100, 0, '.', ' ') . ' ₽')
                    ->sortable(),

                Tables\Columns\TextColumn::make('commission_kopecks')
                    ->label('Комиссия')
                    ->formatStateUsing(static fn (int $state): string => number_format($state / 100, 0, '.', ' ') . ' ₽')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('property.title')
                    ->label('Объект')
                    ->limit(30)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('signed_at')
                    ->label('Подписан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->options(ContractTypeEnum::options()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft'      => 'Черновик',
                        'signed'     => 'Подписан',
                        'terminated' => 'Расторгнут',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index'  => ListContracts::route('/'),
            'create' => CreateContract::route('/create'),
            'view'   => ViewContract::route('/{record}'),
            'edit'   => EditContract::route('/{record}/edit'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\CRM\Filament\Resources;

use App\Domains\CRM\Models\CrmDeal;
use App\Domains\CRM\Models\CrmPipeline;
use App\Domains\CRM\Models\CrmStage;
use App\Domains\CRM\Models\CrmCustomer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CrmDealResource — Filament ресурс для управления сделками.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmDealResource extends Resource
{
    protected static ?string $model = CrmDeal::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Select::make('pipeline_id')
                            ->relationship('pipeline', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (Forms\Components\Select $component, $state) => $component
                                ->getContainer()
                                ->getComponent('stage_id')
                                ?->options(
                                    CrmStage::where('pipeline_id', $state)
                                        ->orderBy('order')
                                        ->pluck('name', 'id')
                                )),
                        Forms\Components\Select::make('stage_id')
                            ->relationship('stage', 'name')
                            ->required(),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
                Forms\Components\Section::make('Финансы и статус')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->default(0)
                            ->suffix('₽'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'new' => 'Новый',
                                'in_progress' => 'В работе',
                                'negotiation' => 'Переговоры',
                                'won' => 'Выигран',
                                'lost' => 'Проигран',
                                'cancelled' => 'Отменен',
                            ])
                            ->required()
                            ->default('new'),
                        Forms\Components\Select::make('priority')
                            ->options([
                                1 => 'Низкий',
                                2 => 'Средний',
                                3 => 'Высокий',
                                4 => 'Срочный',
                                5 => 'Критический',
                            ])
                            ->default(3),
                        Forms\Components\DatePicker::make('expected_close_date')
                            ->label('Ожидаемая дата закрытия'),
                    ])->columns(2),
                Forms\Components\Section::make('Назначение')
                    ->schema([
                        Forms\Components\Select::make('assigned_to_id')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Назначен на'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pipeline.name')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stage.name')
                    ->badge()
                    ->color(fn (CrmStage $stage): string => $stage->color ?? 'gray'),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('value')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'gray',
                        'in_progress' => 'blue',
                        'negotiation' => 'yellow',
                        'won' => 'green',
                        'lost' => 'red',
                        'cancelled' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expected_close_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pipeline')
                    ->relationship('pipeline', 'name'),
                Tables\Filters\SelectFilter::make('stage')
                    ->relationship('stage', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'Новый',
                        'in_progress' => 'В работе',
                        'negotiation' => 'Переговоры',
                        'won' => 'Выигран',
                        'lost' => 'Проигран',
                        'cancelled' => 'Отменен',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->label('Просроченные'),
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
            ->with(['pipeline', 'stage', 'customer', 'assignedTo']);
    }
}

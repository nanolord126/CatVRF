<?php

declare(strict_types=1);

namespace App\Domains\CRM\Filament\Resources;

use App\Domains\CRM\Models\CrmTask;
use App\Domains\CRM\Models\CrmDeal;
use App\Domains\CRM\Models\CrmCustomer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CrmTaskResource — Filament ресурс для управления задачами.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmTaskResource extends Resource
{
    protected static ?string $model = CrmTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

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
                        Forms\Components\Select::make('type')
                            ->options([
                                'call' => 'Звонок',
                                'email' => 'Email',
                                'meeting' => 'Встреча',
                                'follow_up' => 'Follow-up',
                                'document' => 'Документ',
                                'payment' => 'Оплата',
                                'delivery' => 'Доставка',
                                'custom' => 'Кастомная',
                            ])
                            ->required()
                            ->default('custom'),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Низкий',
                                'medium' => 'Средний',
                                'high' => 'Высокий',
                                'urgent' => 'Срочный',
                            ])
                            ->required()
                            ->default('medium'),
                    ])->columns(2),
                Forms\Components\Section::make('Связь')
                    ->schema([
                        Forms\Components\Select::make('deal_id')
                            ->relationship('deal', 'title')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
                Forms\Components\Section::make('Назначение и сроки')
                    ->schema([
                        Forms\Components\Select::make('assigned_to_id')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Назначен на'),
                        Forms\Components\DateTimePicker::make('due_date')
                            ->label('Срок выполнения'),
                        Forms\Components\TextInput::make('location')
                            ->label('Место проведения'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'call' => 'blue',
                        'email' => 'green',
                        'meeting' => 'purple',
                        'follow_up' => 'yellow',
                        'document' => 'gray',
                        'payment' => 'red',
                        'delivery' => 'orange',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('deal.title')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'blue',
                        'completed' => 'green',
                        'cancelled' => 'red',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'yellow',
                        'high' => 'orange',
                        'urgent' => 'red',
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_overdue')
                    ->boolean()
                    ->label('Просрочена')
                    ->getStateUsing(fn (CrmTask $record): bool => $record->due_date && $record->due_date->isPast() && $record->status !== 'completed')
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Ожидает',
                        'in_progress' => 'В работе',
                        'completed' => 'Завершена',
                        'cancelled' => 'Отменена',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Низкий',
                        'medium' => 'Средний',
                        'high' => 'Высокий',
                        'urgent' => 'Срочный',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->label('Просроченные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Завершить')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (CrmTask $record) => $record->complete()),
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
            ->with(['deal', 'customer', 'assignedTo']);
    }
}

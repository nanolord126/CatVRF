<?php declare(strict_types=1);

namespace App\Filament\CRM\Resources;

use App\Services\AuditService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CRM: Задачи для CRM-операторов.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Модель CrmTask хранится в app/Models/CrmTask.php (таблица crm_tasks).
 * AuditService::record() вызывается при завершении/переназначении задачи.
 */
final class CrmTaskResource extends Resource
{
    protected static ?string $model = \App\Models\CrmTask::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static ?string $navigationGroup = 'Задачи';
    protected static ?string $navigationLabel = 'Задачи';
    protected static ?string $modelLabel = 'Задача';
    protected static ?string $pluralModelLabel = 'Задачи';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Задача')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('type')
                        ->label('Тип')
                        ->options([
                            'call'     => 'Позвонить',
                            'email'    => 'Написать письмо',
                            'meeting'  => 'Встреча',
                            'follow_up'=> 'Follow-up',
                            'demo'     => 'Демонстрация',
                        ])
                        ->required()
                        ->columnSpan(1),
                    Select::make('priority')
                        ->label('Приоритет')
                        ->options([
                            'low'    => 'Низкий',
                            'normal' => 'Обычный',
                            'high'   => 'Высокий',
                            'urgent' => 'Срочный',
                        ])
                        ->default('normal')
                        ->columnSpan(1),
                    Select::make('status')
                        ->label('Статус')
                        ->options([
                            'open'        => 'Открыта',
                            'in_progress' => 'В работе',
                            'done'        => 'Выполнена',
                            'cancelled'   => 'Отменена',
                        ])
                        ->default('open')
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('assignee_id')
                        ->label('Ответственный (User ID)')
                        ->numeric()
                        ->columnSpan(1),
                    DateTimePicker::make('due_at')
                        ->label('Срок выполнения')
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('related_lead_id')
                        ->label('Лид ID (если есть)')
                        ->numeric()
                        ->columnSpan(1),
                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    Textarea::make('result')
                        ->label('Результат')
                        ->rows(2)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'meeting' => 'warning',
                        'demo'    => 'success',
                        default   => 'gray',
                    }),
                TextColumn::make('priority')
                    ->label('Приоритет')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high'   => 'warning',
                        'low'    => 'gray',
                        default  => 'info',
                    }),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cancelled'   => 'danger',
                        'in_progress' => 'warning',
                        default       => 'gray',
                    }),
                TextColumn::make('assignee_id')
                    ->label('Ответственный')
                    ->searchable(),
                TextColumn::make('due_at')
                    ->label('Срок')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn ($state): string => $state && $state < now() ? 'danger' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'open'        => 'Открыта',
                        'in_progress' => 'В работе',
                        'done'        => 'Выполнена',
                        'cancelled'   => 'Отменена',
                    ]),
                SelectFilter::make('priority')
                    ->label('Приоритет')
                    ->options([
                        'low'    => 'Низкий',
                        'normal' => 'Обычный',
                        'high'   => 'Высокий',
                        'urgent' => 'Срочный',
                    ]),
                Filter::make('overdue')
                    ->label('Просроченные')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotIn('status', ['done', 'cancelled'])
                        ->where('due_at', '<', now())),
            ])
            ->actions([
                EditAction::make(),
                Action::make('markDone')
                    ->label('Выполнено')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => !in_array($record->status, ['done', 'cancelled']))
                    ->action(function (Model $record): void {
                        $old = ['status' => $record->status];

                        $record->update(['status' => 'done']);

                        app(AuditService::class)->record(
                            'crm_task_completed',
                            get_class($record),
                            $record->id,
                            $old,
                            ['status' => 'done'],
                        );

                        Notification::make()
                            ->title('Задача отмечена выполненной')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->defaultSort('due_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\CRM\Resources\CrmTaskResource\Pages\ListCrmTasks::route('/'),
            'create' => \App\Filament\CRM\Resources\CrmTaskResource\Pages\CreateCrmTask::route('/create'),
            'edit'   => \App\Filament\CRM\Resources\CrmTaskResource\Pages\EditCrmTask::route('/{record}/edit'),
        ];
    }
}

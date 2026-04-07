<?php
declare(strict_types=1);

namespace App\Domains\Art\Filament\Tenant\Resources;



use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use App\Domains\Art\Models\Project;
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

final class ProjectResource extends Resource
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

    protected static ?string $model = Project::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Параметры проекта')
                ->description('B2C/B2B настройки, дедлайны и обязательные поля канона')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('brief')
                        ->label('Бриф')
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\ToggleButtons::make('mode')
                        ->label('Режим (B2C/B2B)')
                        ->options([
                            'b2c' => 'B2C',
                            'b2b' => 'B2B',
                        ])
                        ->required()
                        ->default('b2c')
                        ->inline(),
                    Forms\Components\Select::make('artist_id')
                        ->label('Художник')
                        ->relationship('artist', 'name')
                        ->required()
                        ->searchable(),
                    Forms\Components\DateTimePicker::make('deadline_at')
                        ->label('Дедлайн')
                        ->minDate(Carbon::now()->addDay()),
                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'draft' => 'Черновик',
                            'active' => 'Активен',
                            'completed' => 'Завершён',
                            'cancelled' => 'Отменён',
                        ])
                        ->default('draft'),
                    Forms\Components\TextInput::make('budget_cents')
                        ->label('Бюджет, копейки')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Forms\Components\Textarea::make('acceptance_criteria')
                        ->label('Критерии приёмки (meta.acceptance)')
                        ->rows(3)
                        ->statePath('meta.acceptance')
                        ->placeholder('Детально: что считается выполнением проекта')
                        ->columnSpanFull(),
                ])->columns(3),
            Forms\Components\Section::make('Финансы и SLA')
                ->schema([
                    Forms\Components\TextInput::make('meta.commission_percent')
                        ->label('Комиссия, %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(30)
                        ->default(14),
                    Forms\Components\TextInput::make('meta.idempotency_key')
                        ->label('Idempotency Key')
                        ->maxLength(64)
                        ->helperText('Контроль повторных платежей/инициаций'),
                    Forms\Components\Toggle::make('meta.requires_ml_review')
                        ->label('Требуется ML-фрод скоринг')
                        ->default(true),
                    Forms\Components\KeyValue::make('meta.sla')
                        ->label('SLA параметры')
                        ->keyLabel('Показатель')
                        ->valueLabel('Значение')
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('meta.milestones')
                        ->label('Майлстоуны')
                        ->schema([
                            Forms\Components\TextInput::make('title')->label('Задача')->required(),
                            Forms\Components\TextInput::make('owner')->label('Ответственный'),
                            Forms\Components\DatePicker::make('due_date')->label('Срок'),
                            Forms\Components\Toggle::make('requires_review')->label('Нужен ревью')->default(true),
                        ])
                        ->default([])
                        ->columnSpanFull()
                        ->orderable()
                        ->collapsible(),
                ])->columns(3),
            Forms\Components\Section::make('Контроль качества и рисков')
                ->description('Поле канона: fraud-check, correlation_id, audit')
                ->schema([
                    Forms\Components\TextInput::make('meta.risk_score')
                        ->label('Предварительный риск-скор')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->content('Все мутации обязательны: fraud-check, $this->db->transaction, correlation_id, audit log'),
                    Forms\Components\TextInput::make('meta.geo_hash')
                        ->label('Geo hash')
                        ->helperText('Изолирует данные по филиалам'),
                    Forms\Components\TextInput::make('meta.fraud_reference')
                        ->label('Fraud reference')
                        ->maxLength(128),
                    Forms\Components\Textarea::make('meta.risk_notes')
                        ->label('Комментарии по рискам')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('meta.audit_required')
                        ->label('Обязательный аудит')
                        ->default(true),
                ])->columns(3),
            Forms\Components\Section::make('Клиентские данные')
                ->schema([
                    Forms\Components\TextInput::make('business_group_id')
                        ->label('Business Group ID')
                        ->numeric()
                        ->helperText('Tenant-aware scoping для филиалов'),
                    Forms\Components\TextInput::make('tenant_id')
                        ->label('Tenant ID')
                        ->numeric()
                        ->required(),
                    Forms\Components\KeyValue::make('preferences')
                        ->label('Предпочтения')
                        ->keyLabel('Ключ')
                        ->valueLabel('Значение')
                        ->columnSpanFull(),
                    Forms\Components\KeyValue::make('tags')
                        ->label('Теги')
                        ->keyLabel('Ключ')
                        ->valueLabel('Значение')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('meta.client_notes')
                        ->label('Комментарий клиента')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Технические поля')
                ->schema([
                    Forms\Components\TextInput::make('uuid')->label('UUID')->disabled()->dehydrated(false),
                    Forms\Components\TextInput::make('correlation_id')->label('Correlation ID')->disabled()->dehydrated(false),
                    Forms\Components\KeyValue::make('meta.aux')
                        ->label('Служебные значения')
                        ->keyLabel('Ключ')
                        ->valueLabel('Значение'),
                    Forms\Components\Placeholder::make('audit_hint')
                        ->label('Audit / Fraud')
                        ->content('fraud-check + correlation_id обязательны'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Название')->searchable()->sortable()->limit(40),
            TextColumn::make('artist.name')->label('Художник')->sortable()->toggleable(),
            TextColumn::make('mode')->label('B2C/B2B')->badge()->colors([
                'success' => 'b2c',
                'warning' => 'b2b',
            ]),
            TextColumn::make('status')->label('Статус')->badge()->colors([
                'primary' => 'draft',
                'success' => 'active',
                'info' => 'completed',
                'danger' => 'cancelled',
            ]),
            TextColumn::make('budget_cents')->label('Бюджет')->money('RUB', divideBy: 100)->sortable(),
            TextColumn::make('deadline_at')->label('Дедлайн')->since(),
            TextColumn::make('business_group_id')->label('BG')->sortable()->toggleable(),
            TextColumn::make('tenant_id')->label('Tenant')->sortable(),
            TextColumn::make('meta.contract_number')
                ->label('Договор')
                ->state(fn (Project $record) => data_get($record->meta, 'contract_number', '—'))
                ->toggleable(),
            TextColumn::make('meta.service_level')
                ->label('SLA')
                ->state(fn (Project $record) => data_get($record->meta, 'service_level', '—'))
                ->toggleable(),
            TextColumn::make('tags')
                ->label('Теги')
                ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_keys($state)) : '—')
                ->limit(30)
                ->toggleable(),
            TextColumn::make('preferences')
                ->label('Предпочтения')
                ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_keys($state)) : '—')
                ->limit(30)
                ->toggleable(),
            TextColumn::make('correlation_id')->label('Correlation')->copyable()->toggleable(),
            TextColumn::make('created_at')->label('Создан')->dateTime()->sortable(),
            TextColumn::make('updated_at')->label('Обновлён')->dateTime()->sortable(),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'active' => 'Активен',
                        'completed' => 'Завершён',
                        'cancelled' => 'Отменён',
                    ]),
                Tables\Filters\Filter::make('mode_b2b')->label('Только B2B')->query(fn (Builder $query) => $query->where('mode', 'b2b')),
                Tables\Filters\Filter::make('deadline_next_week')
                    ->label('Дедлайн < 7 дней')
                    ->query(fn (Builder $query) => $query->whereBetween('deadline_at', [Carbon::now(), Carbon::now()->addDays(7)])),
                Tables\Filters\Filter::make('budget_over_100k')
                    ->label('Бюджет > 100 000 ₽')
                    ->query(fn (Builder $query) => $query->where('budget_cents', '>', 100_000_00)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_completed')
                    ->label('Завершить')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn (Project $record): bool => $record->status !== 'completed')
                    ->action(function (Project $record): void {
                        $correlationId = (string) Str::uuid();

                        $this->db->transaction(static function () use ($record, $correlationId): void {
                            $record->update([
                                'status' => 'completed',
                                'meta' => array_merge($record->meta ?? [], ['completed_at' => Carbon::now()->toIso8601String()]),
                                'correlation_id' => $record->correlation_id ?: $correlationId,
                            ]);
                        });

                        $this->logger->info('Project marked completed from Filament', [
                            'project_id' => $record->id,
                            'correlation_id' => $record->correlation_id ?: $correlationId,
                            'tenant_id' => $record->tenant_id,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->requiresConfirmation()
                        ->action(function (array $records): void {
                            $correlationId = (string) Str::uuid();
                            $this->db->transaction(static function () use ($records, $correlationId): void {
                                Project::query()->whereIn('id', $records)->update([
                                    'status' => 'active',
                                    'correlation_id' => $correlationId,
                                ]);
                            });

                            $this->logger->info('Projects bulk-activated from Filament', [
                                'correlation_id' => $correlationId,
                                'count' => count($records),
                            ]);
                        }),
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();
        $data['tenant_id'] = $data['tenant_id'] ?? (function_exists('tenant') && tenant() ? (int) tenant()->id : 0);
        $data['mode'] = $data['mode'] ?? (!empty($data['inn']) || !empty($data['business_card_id']) ? 'b2b' : 'b2c');

        return $data;
    }
}

final class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;
}

final class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ProjectResource::mutateFormDataBeforeCreate($data);
    }
}

final class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['correlation_id'] = $data['correlation_id'] ?? (string) Str::uuid();

        return $data;
    }
}

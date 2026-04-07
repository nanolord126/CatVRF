<?php declare(strict_types=1);

namespace App\Filament\Admin\Resources;


use Psr\Log\LoggerInterface;
use App\Models\AuditLog;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AuditLogResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static ?string $model = AuditLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Безопасность';
    protected static ?string $navigationLabel = 'Журнал аудита';
    protected static ?string $modelLabel = 'Запись аудита';
    protected static ?string $pluralModelLabel = 'Журнал аудита';
    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Событие')
                ->columns(2)
                ->schema([
                    Placeholder::make('action')
                        ->label('Действие')
                        ->content(fn (AuditLog $record): string => $record->action)
                        ->columnSpan(1),
                    Placeholder::make('subject_type')
                        ->label('Тип объекта')
                        ->content(fn (AuditLog $record): string => $record->subject_type)
                        ->columnSpan(1),
                    Placeholder::make('subject_id')
                        ->label('ID объекта')
                        ->content(fn (AuditLog $record): string => (string) ($record->subject_id ?? '—'))
                        ->columnSpan(1),
                    Placeholder::make('user_id')
                        ->label('User ID')
                        ->content(fn (AuditLog $record): string => (string) ($record->user_id ?? '—'))
                        ->columnSpan(1),
                    Placeholder::make('ip_address')
                        ->label('IP-адрес')
                        ->content(fn (AuditLog $record): string => (string) ($record->ip_address ?? '—'))
                        ->columnSpan(1),
                    Placeholder::make('correlation_id')
                        ->label('Correlation ID')
                        ->content(fn (AuditLog $record): string => (string) ($record->correlation_id ?? '—'))
                        ->columnSpan(1),
                    Placeholder::make('created_at')
                        ->label('Время')
                        ->content(fn (AuditLog $record): string => $record->created_at?->format('d.m.Y H:i:s') ?? '—')
                        ->columnSpan(2),
                ]),
            Section::make('Изменения')
                ->collapsed()
                ->columns(2)
                ->schema([
                    KeyValue::make('old_values')
                        ->label('Было')
                        ->disabled()
                        ->columnSpan(1),
                    KeyValue::make('new_values')
                        ->label('Стало')
                        ->disabled()
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Действие')
                    ->badge()
                    ->searchable()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'delete') => 'danger',
                        str_contains($state, 'create') => 'success',
                        str_contains($state, 'update') => 'warning',
                        default                         => 'gray',
                    }),
                TextColumn::make('subject_type')
                    ->label('Объект')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                TextColumn::make('subject_id')
                    ->label('ID объекта')
                    ->searchable(),
                TextColumn::make('user_id')
                    ->label('User ID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('tenant_id')
                    ->label('Tenant ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('correlation_id')
                    ->label('Correlation ID')
                    ->copyable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Действие')
                    ->options([
                        'created'       => 'Создание',
                        'updated'       => 'Изменение',
                        'deleted'       => 'Удаление',
                        'payment_init'  => 'Платёж',
                        'payout'        => 'Вывод',
                        'payroll_paid'  => 'Зарплата',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\AuditLogResource\Pages\ListAuditLogs::route('/'),
            'view'  => \App\Filament\Admin\Resources\AuditLogResource\Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Audit\Filament\Resources;

use App\Domains\Audit\Models\AuditLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

final class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('view_any_audit_logs') ?? true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->searchable()
                    ->toggleable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('subject_id')
                    ->toggleable()
                    ->label('Subject ID'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('correlation_id')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->limit(10),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->description(fn (AuditLog $record): string => $record->created_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\Filter::make('action')
                    ->form([
                        TextInput::make('action')->placeholder('e.g., order_created'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['action']) && $data['action']) {
                            $query->where('action', 'like', '%'.$data['action'].'%');
                        }
                    }),
                Tables\Filters\Filter::make('subject_type')
                    ->form([
                        TextInput::make('subject_type')->placeholder('e.g., Order'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['subject_type']) && $data['subject_type']) {
                            $query->where('subject_type', 'like', '%'.$data['subject_type'].'%');
                        }
                    }),
                Tables\Filters\Filter::make('ip_address')
                    ->form([
                        TextInput::make('ip_address')->placeholder('e.g., 192.168.1.1'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['ip_address']) && $data['ip_address']) {
                            $query->where('ip_address', $data['ip_address']);
                        }
                    }),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['from']) && $data['from']) {
                            $query->where('created_at', '>=', $data['from']);
                        }
                        if (isset($data['until']) && $data['until']) {
                            $query->where('created_at', '<=', $data['until']);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Audit\Filament\Resources\AuditLogResource\Pages\ListAuditLogs::route('/'),
            'view' => \App\Domains\Audit\Filament\Resources\AuditLogResource\Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}

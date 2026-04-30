<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityEventResource\Pages;
use App\Models\SecurityEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SecurityEventResource extends Resource
{
    protected static ?string $model = SecurityEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Event Information')
                    ->schema([
                        Forms\Components\TextInput::make('event_id')
                            ->disabled()
                            ->copyable()
                            ->label('Event ID'),
                        Forms\Components\Select::make('event_type')
                            ->options([
                                'auth_failure' => 'Authentication Failure',
                                'brute_force' => 'Brute Force Attack',
                                'fraud_detected' => 'Fraud Detected',
                                'aml_alert' => 'AML Alert',
                                'suspicious_activity' => 'Suspicious Activity',
                                'credential_stuffing' => 'Credential Stuffing',
                                'insider_threat' => 'Insider Threat',
                                'data_breach_attempt' => 'Data Breach Attempt',
                            ])
                            ->required(),
                        Forms\Components\Select::make('severity')
                            ->options([
                                'info' => 'Info',
                                'warning' => 'Warning',
                                'critical' => 'Critical',
                            ])
                            ->required()
                            ->default('info'),
                        Forms\Components\DateTimePicker::make('detected_at')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Source Information')
                    ->schema([
                        Forms\Components\TextInput::make('source_ip')
                            ->label('Source IP')
                            ->ip(),
                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent'),
                        Forms\Components\TextInput::make('correlation_id')
                            ->label('Correlation ID')
                            ->disabled()
                            ->copyable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Resolution')
                    ->schema([
                        Forms\Components\Toggle::make('resolved')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('resolved_at', now());
                                    $set('resolved_by', auth()->id());
                                } else {
                                    $set('resolved_at', null);
                                    $set('resolved_by', null);
                                }
                            }),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->disabled(),
                        Forms\Components\TextInput::make('resolved_by')
                            ->disabled(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->addActionLabel('Add metadata'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'tenant']))
            ->columns([
                Tables\Columns\TextColumn::make('event_id')
                    ->label('Event ID')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Event Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'info' => 'info',
                        'warning' => 'warning',
                        'critical' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('source_ip')
                    ->label('Source IP')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('detected_at')
                    ->label('Detected At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('resolved')
                    ->boolean()
                    ->label('Resolved'),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Resolved At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options([
                        'auth_failure' => 'Authentication Failure',
                        'brute_force' => 'Brute Force Attack',
                        'fraud_detected' => 'Fraud Detected',
                        'aml_alert' => 'AML Alert',
                        'suspicious_activity' => 'Suspicious Activity',
                        'credential_stuffing' => 'Credential Stuffing',
                        'insider_threat' => 'Insider Threat',
                        'data_breach_attempt' => 'Data Breach Attempt',
                    ]),
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'info' => 'Info',
                        'warning' => 'Warning',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\TernaryFilter::make('resolved')
                    ->placeholder('All events')
                    ->trueLabel('Resolved')
                    ->falseLabel('Unresolved'),
                Tables\Filters\Filter::make('detected_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query) => $query->whereDate('detected_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($query) => $query->whereDate('detected_at', '<=', $data['until'])
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (SecurityEvent $record) {
                        $record->update([
                            'resolved' => true,
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id(),
                        ]);
                    })
                    ->visible(fn (SecurityEvent $record) => ! $record->resolved),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resolve')
                        ->label('Resolve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'resolved' => true,
                                    'resolved_at' => now(),
                                    'resolved_by' => auth()->id(),
                                ]);
                            }
                        }),
                ]),
            ])
            ->defaultSort('detected_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSecurityEvents::route('/'),
            'create' => Pages\CreateSecurityEvent::route('/create'),
            'view' => Pages\ViewSecurityEvent::route('/{record}'),
            'edit' => Pages\EditSecurityEvent::route('/{record}/edit'),
        ];
    }
}

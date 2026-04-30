<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InsiderThreatResource\Pages;
use App\Models\InsiderThreatLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

final class InsiderThreatResource extends Resource
{
    protected static ?string $model = InsiderThreatLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Auth::user()?->role?->isPlatformAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('action_type')
                    ->label('Action')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('anomaly_score')
                    ->label('Anomaly Score')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 4))
                    ->color(fn ($state) => match(true) {
                        $state >= 0.9 => 'danger',
                        $state >= 0.7 => 'warning',
                        $state >= 0.5 => 'primary',
                        default => 'success',
                    }),

                Tables\Columns\BadgeColumn::make('severity')
                    ->label('Severity')
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'high',
                        'primary' => 'medium',
                        'success' => 'low',
                    ]),

                Tables\Columns\IconColumn::make('was_blocked')
                    ->label('Blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                Tables\Columns\IconColumn::make('requires_review')
                    ->label('Review Required')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-check'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Detected At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),

                Tables\Filters\TernaryFilter::make('was_blocked')
                    ->label('Blocked Actions'),

                Tables\Filters\TernaryFilter::make('requires_review')
                    ->label('Requires Review'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('mark_reviewed')
                    ->label('Mark Reviewed')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->action(function (InsiderThreatLog $record) {
                        $record->markAsReviewed(Auth::id(), 'Reviewed via dashboard');
                    })
                    ->visible(fn ($record) => $record->requires_review && ! $record->is_reviewed),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInsiderThreats::route('/'),
            'view' => Pages\ViewInsiderThreat::route('/{record}'),
        ];
    }
}

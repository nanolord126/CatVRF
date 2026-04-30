<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InsiderThreatLogResource\Pages;
use App\Models\InsiderThreatLog;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;

final class InsiderThreatLogResource extends Resource
{
    protected static ?string $model = InsiderThreatLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-shield';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('anomaly_score')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn (float $state): string => number_format($state * 100, 1).'%')
                    ->color(fn (float $state): string => match (true) {
                        $state >= 0.85 => 'danger',
                        $state >= 0.7 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('severity')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('was_blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('requires_review')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_reviewed')
                    ->boolean()
                    ->label('Reviewed'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                    ]),
                Tables\Filters\TernaryFilter::make('was_blocked'),
                Tables\Filters\TernaryFilter::make('requires_review'),
                Tables\Filters\TernaryFilter::make('is_reviewed')
                    ->label('Reviewed')
                    ->falseLabel('Pending'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-check')
                    ->visible(fn (InsiderThreatLog $record): bool => $record->requires_review && ! $record->is_reviewed)
                    ->form([
                        Textarea::make('notes')
                            ->label('Review Notes')
                            ->rows(3),
                    ])
                    ->action(function (InsiderThreatLog $record, array $data): void {
                        $record->markAsReviewed(auth()->id(), $data['notes'] ?? null);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_reviewed')
                        ->label('Mark as Reviewed')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (array $records): void {
                            foreach ($records as $record) {
                                $record->markAsReviewed(auth()->id());
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListInsiderThreatLogs::route('/'),
            'view' => Pages\ViewInsiderThreatLog::route('/{record}'),
        ];
    }
}

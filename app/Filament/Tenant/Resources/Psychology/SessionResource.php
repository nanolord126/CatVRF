<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology;

use App\Domains\Medical\Psychology\Models\PsychologicalSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

/**
 * Управление сессиями (Протоколы).
 * СТРОГО ФЗ-152: Доступ логируется.
 */
final class SessionResource extends Resource
{
    protected static ?string $model = PsychologicalSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Psychological Services';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        // Каждая попытка редактирования или просмотра протокола логируется
        Log::channel('audit')->warning('Confidential Session Data Access', [
            'user_id' => auth()->id(),
            'target_session_id' => $form->getRecord()?->id,
        ]);

        return $form->schema([
            Forms\Components\Section::make('Session Protocol')
                ->description('Sensitive therapeutic information')
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('booking_info')
                        ->content(fn ($record) => "Booking: {$record?->booking?->uuid}"),
                    Forms\Components\DateTimePicker::make('started_at')
                        ->required(),
                    Forms\Components\DateTimePicker::make('ended_at'),
                    Forms\Components\RichEditor::make('therapist_notes')
                        ->label('Session Notes (Protected)')
                        ->columnSpanFull()
                        ->required(),
                    Forms\Components\Textarea::make('homework')
                        ->label('Homework for client')
                        ->columnSpanFull(),
                    Forms\Components\Section::make('Emotional Tracking')
                        ->schema([
                            Forms\Components\KeyValue::make('emotional_state')
                                ->addable(false)
                                ->deletable(false)
                                ->editableKeys(false)
                                ->default([
                                    'Anxiety' => 'Low',
                                    'Depression' => 'N/A',
                                    'Improvement' => 'Visible',
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking.client.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('booking.psychologist.full_name')
                    ->label('Therapist')
                    ->searchable(),
                Tables\Columns\TextColumn::make('duration')
                    ->state(fn ($record) => $record->ended_at ? $record->started_at->diffInMinutes($record->ended_at) . ' min' : 'Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Psychology\SessionResource\Pages\ListSessions::route('/'),
        ];
    }
}

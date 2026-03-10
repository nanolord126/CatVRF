<?php

namespace App\Filament\Tenant\Resources\HRJobVacancyResource\RelationManagers;

use App\Models\HRVacancyMatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'matches';

    protected static ?string $title = 'Ecosystem AI Recommendations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user_id')
            ->columns([
                Tables\Columns\TextColumn::make('candidate.name')
                    ->label('Talent Name')
                    ->description(fn (HRVacancyMatch $record) => $record->candidate->email),
                Tables\Columns\TextColumn::make('match_score')
                    ->label('AI Total')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->badge()
                    ->colors([
                        'danger' => static fn ($state): bool => $state < 50,
                        'warning' => static fn ($state): bool => $state >= 50 && $state < 80,
                        'success' => static fn ($state): bool => $state >= 80,
                    ]),
                Tables\Columns\TextColumn::make('semantic_score')
                    ->label('Semantic')
                    ->numeric(2)
                    ->color('info'),
                Tables\Columns\TextColumn::make('skill_score')
                    ->label('Skills')
                    ->numeric(2)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('geo_score')
                    ->label('Geo')
                    ->numeric(2)
                    ->color('success'),
                Tables\Columns\TextColumn::make('match_reasons')
                    ->label('AI Insight')
                    ->formatStateUsing(function ($state) {
                        return collect($state)->join(' • ');
                    })
                    ->wrap(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('View Profile')
                    ->icon('heroicon-o-user')
                    ->url(fn (HRVacancyMatch $record) => "mailto:{$record->candidate->email}"), // Quick action for 2026 iteration
            ])
            ->bulkActions([]);
    }
}

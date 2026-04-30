<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BruteForceAttemptResource\Pages;
use App\Models\BruteForceAttempt;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BruteForceAttemptResource extends Resource
{
    protected static ?string $model = BruteForceAttempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 1;

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
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('attempt_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'login' => 'danger',
                        'register' => 'warning',
                        'recovery' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('was_successful')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\IconColumn::make('was_blocked')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\TextColumn::make('block_reason')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attempt_type')
                    ->options([
                        'login' => 'Login',
                        'register' => 'Register',
                        'recovery' => 'Recovery',
                    ]),
                Tables\Filters\TernaryFilter::make('was_blocked'),
                Tables\Filters\TernaryFilter::make('was_successful'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
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
            'index' => Pages\ListBruteForceAttempts::route('/'),
        ];
    }
}

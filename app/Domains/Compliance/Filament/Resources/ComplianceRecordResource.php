<?php declare(strict_types=1);

namespace App\Domains\Compliance\Filament\Resources;

use App\Domains\Compliance\Models\ComplianceRecord;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class ComplianceRecordResource extends Resource
{
    protected static ?string $model = ComplianceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Compliance';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mdlp' => 'info',
                        'mercury' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('document_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'mdlp' => 'MDLP',
                        'mercury' => 'Mercury',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'verified' => 'Verified',
                        'failed' => 'Failed',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}

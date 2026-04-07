<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentalReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Class ListDentalReview
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ListDentalReview extends ListRecords
{
    protected static string $resource = DentalReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить отзыв')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('client_id')->label('Пациент ID')->sortable(),
                TextColumn::make('dentist_id')->label('Врач ID')->sortable(),
                TextColumn::make('rating')->label('Оценка')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state . '/100')
                    ->color(fn ($state) => $state >= 70 ? 'success' : ($state >= 40 ? 'warning' : 'danger')),
                TextColumn::make('comment')->label('Комментарий')->limit(80)->wrap(),
                IconColumn::make('is_verified')->label('Проверен')->boolean()->sortable(),
                TextColumn::make('correlation_id')->label('Corr. ID')->toggleable(isToggledHiddenByDefault: true)->limit(16),
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Только проверенные'),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Class ListDentist
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ListDentist extends ListRecords
{
    protected static string $resource = DentistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить врача')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('full_name')->label('ФИО')->sortable()->searchable()->weight('bold'),
                TextColumn::make('specialization')->label('Специализация')->sortable()->searchable()
                    ->badge()->color('primary'),
                TextColumn::make('experience_years')->label('Опыт (лет)')->sortable(),
                TextColumn::make('rating')->label('Рейтинг')->sortable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? '★ ' . $state . '/100' : '—'),
                IconColumn::make('is_active')->label('Активен')->boolean()->sortable(),
                TextColumn::make('correlation_id')->label('Corr. ID')->toggleable(isToggledHiddenByDefault: true)->limit(16),
                TextColumn::make('created_at')->label('Добавлен')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('specialization')
                    ->label('Специализация')
                    ->options(['Therapy' => 'Терапия', 'Surgery' => 'Хирургия', 'Orthodontics' => 'Ортодонтия', 'Implantology' => 'Имплантология']),
                TernaryFilter::make('is_active')->label('Активные'),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}

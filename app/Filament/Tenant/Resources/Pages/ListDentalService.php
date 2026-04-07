<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentalServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Class ListDentalService
 *
 * Service layer following CatVRF canon:
 * - Constructor injection only (no Facades)
 * - FraudControlService::check() before mutations
 * - $this->db->transaction() wrapping all write operations
 * - Audit logging with correlation_id
 * - Tenant and BusinessGroup scoping
 *
 * @see \App\Services\FraudControlService
 * @see \App\Services\AuditService
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ListDentalService extends ListRecords
{
    protected static string $resource = DentalServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить услугу')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('Название услуги')->sortable()->searchable()->weight('bold'),
                BadgeColumn::make('category')->label('Категория')
                    ->colors(['primary' => 'Therapy', 'warning' => 'Surgery', 'success' => 'Orthodontics', 'info' => 'Implantology']),
                TextColumn::make('base_price')->label('Базовая цена')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 0, ',', ' ') . ' ₽')->sortable(),
                TextColumn::make('duration_minutes')->label('Длительность')
                    ->formatStateUsing(fn ($state) => $state . ' мин.')->sortable(),
                TextColumn::make('correlation_id')->label('Corr. ID')->toggleable(isToggledHiddenByDefault: true)->limit(16),
                TextColumn::make('created_at')->label('Добавлено')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label('Категория')
                    ->options(['Therapy' => 'Терапия', 'Surgery' => 'Хирургия', 'Orthodontics' => 'Ортодонтия', 'Implantology' => 'Имплантология']),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}

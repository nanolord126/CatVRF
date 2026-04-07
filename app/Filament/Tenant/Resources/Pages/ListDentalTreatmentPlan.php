<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\DentalTreatmentPlanResource;
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

final class ListDentalTreatmentPlan extends ListRecords
{
    protected static string $resource = DentalTreatmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Создать план лечения')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')->label('Название')->sortable()->searchable()->weight('bold'),
                TextColumn::make('dentist_id')->label('Врач ID')->sortable(),
                TextColumn::make('client_id')->label('Пациент ID')->sortable(),
                BadgeColumn::make('status')->label('Статус')
                    ->colors([
                        'gray'    => 'draft',
                        'success' => 'active',
                        'primary' => 'finished',
                        'danger'  => 'archived',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'active'   => 'Активен',
                        'finished' => 'Завершён',
                        'archived' => 'Архив',
                        default    => $state,
                    }),
                TextColumn::make('estimated_budget')->label('Бюджет')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 0, ',', ' ') . ' ₽' : '—')->sortable(),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')
                    ->options(['draft' => 'Черновик', 'active' => 'Активен', 'finished' => 'Завершён', 'archived' => 'Архив']),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}

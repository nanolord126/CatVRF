<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\StationeryGiftSet\Pages;

use use App\Filament\Tenant\Resources\StationeryGiftSetResource;;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ListStationeryGiftSet extends ListRecords
{
    protected static string $resource = StationeryGiftSetResource::class;

    public function getTitle(): string
    {
        return 'List StationeryGiftSet';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }
}
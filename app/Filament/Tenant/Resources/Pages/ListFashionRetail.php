<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FashionRetail\Pages;

use use App\Filament\Tenant\Resources\FashionRetailResource;;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\{EditAction, DeleteAction};
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ListFashionRetail extends ListRecords
{
    protected static string $resource = FashionRetailResource::class;

    public function getTitle(): string
    {
        return 'List FashionRetail';
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
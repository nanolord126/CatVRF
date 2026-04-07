<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\FashionProductResource;
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

final class ListFashionProduct extends ListRecords
{
    protected static string $resource = FashionProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Добавить товар')->icon('heroicon-o-plus'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->label('Название')->sortable()->searchable()->weight('bold'),
                TextColumn::make('sku')->label('SKU')->copyable()->badge()->searchable(),
                TextColumn::make('brand')->label('Бренд')->sortable()->searchable(),
                TextColumn::make('color')->label('Цвет')->sortable(),
                TextColumn::make('price_b2c')->label('Цена B2C')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 0, ',', ' ') . ' ₽')->sortable(),
                TextColumn::make('price_b2b')->label('Цена B2B')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 0, ',', ' ') . ' ₽' : '—')->sortable(),
                TextColumn::make('stock_quantity')->label('Остаток')
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success'))
                    ->sortable(),
                BadgeColumn::make('status')->label('Статус')
                    ->colors(['success' => 'active', 'warning' => 'draft', 'danger' => 'archived'])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft'    => 'Черновик',
                        'archived' => 'Архив',
                        default    => $state,
                    }),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('brand')->label('Бренд')->searchable(),
                SelectFilter::make('status')->label('Статус')
                    ->options(['active' => 'Активен', 'draft' => 'Черновик', 'archived' => 'Архив']),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}

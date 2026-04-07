<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeverageReviewResource;
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
 * Class ListBeverageReview
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class ListBeverageReview extends ListRecords
{
    protected static string $resource = BeverageReviewResource::class;

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
                TextColumn::make('rating')->label('Рейтинг')
                    ->formatStateUsing(fn ($state) => '★' . $state . '/5')
                    ->color(fn ($state) => match(true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default     => 'danger',
                    })->sortable(),
                TextColumn::make('comment')->label('Комментарий')->limit(50)->wrap(),
                TextColumn::make('user_id')->label('Пользователь ID')->sortable(),
                TextColumn::make('shop_id')->label('Заведение ID')->sortable(),
                TextColumn::make('item_id')->label('Позиция ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_verified')->label('Подтверждён')->boolean()
                    ->trueIcon('heroicon-o-check-badge')->falseIcon('heroicon-o-clock')
                    ->trueColor('success')->falseColor('warning'),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_verified')->label('Подтверждён'),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')->striped();
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Filament\Resources;

use App\Domains\Flowers\Models\FlowerOrder;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FlowerOrderResource — CatVRF 2026 Component.
 *
 * Filament resource for managing flower orders.
 * Tenant-scoped: all data filtered by current tenant.
 */
final class FlowerOrderResource extends BaseOptimizedResource
{
    protected static ?string $model = FlowerOrder::class;

    protected static ?string $slug = 'flower-orders';

    protected static ?string $navigationGroup = 'Flowers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')->options([
                'pending' => 'Ожидание',
                'confirmed' => 'Подтверждено',
                'preparing' => 'Готовится',
                'delivered' => 'Доставлено',
                'cancelled' => 'Отменено',
            ])->required(),
            Forms\Components\TextInput::make('total_amount')->numeric(),
            Forms\Components\TextInput::make('commission_amount')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('order_number')->searchable(),
            Tables\Columns\TextColumn::make('total_amount'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status'),
        ])->actions([
            Tables\Actions\ViewAction::make(),
        ]);
    }

    /**
     * Relations to eager load for Flowers
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}

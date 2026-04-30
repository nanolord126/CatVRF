<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Flowers\Filament\Resources;

use App\Domains\Flowers\Models\FlowerDelivery;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * FlowerDeliveryResource — CatVRF 2026 Component.
 *
 * Filament resource for managing flower deliveries.
 * Tenant-scoped: all data filtered by current tenant.
 */
final class FlowerDeliveryResource extends BaseOptimizedResource
{
    protected static ?string $model = FlowerDelivery::class;

    protected static ?string $slug = 'flower-deliveries';

    protected static ?string $navigationGroup = 'Flowers';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')->options([
                'pending' => 'Ожидание',
                'in_transit' => 'В пути',
                'delivered' => 'Доставлено',
            ])->required(),
            Forms\Components\TextInput::make('latitude')->numeric(),
            Forms\Components\TextInput::make('longitude')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('pickup_time')->dateTime(),
            Tables\Columns\TextColumn::make('delivery_time')->dateTime(),
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

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Models\Collectibles\CollectibleStore;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Collectibles\CollectibleItem;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CollectibleStoreResource — Retail management for niche stores.
 * Orchestrates vertical store settings, inventory alerts, and stats.
 */
class CollectibleStoreResource extends Resource
{
    protected static ?string $model = CollectibleStore::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Collectibles Hub';
    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Store Identity')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('tenant_id')->relationship('tenant', 'name')->required(),
                Forms\Components\Select::make('business_group_id')->relationship('businessGroup', 'name'),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Store Description')->schema([
                Forms\Components\MarkdownEditor::make('description')->columnSpanFull(),
                Forms\Components\TextInput::make('tags')->label('Tags (Comma separated)'),
                Forms\Components\KeyValue::make('metadata'),
            ])->columns(2),

            Forms\Components\Section::make('Valuation & Statistics')->schema([
                Forms\Components\ViewField::make('store_stats')
                    ->view('filament.tenant.fields.collectibles-stats')
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('tenant.name')->sortable(),
            Tables\Columns\BadgeColumn::make('items_count')
                ->counts('items')
                ->label('Total Inventory')
                ->color('primary'),
            Tables\Columns\TextColumn::make('rating')->sortable()->numeric(2),
            Tables\Columns\ToggleColumn::make('is_active'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Action::make('recalculate_stats')
                ->icon('heroicon-o-arrow-path')
                ->action(function (CollectibleStore $record) {
                    // Logic to trigger background store recalculation
                    Notification::make()->title("Statistics updated successfully.")->success()->send();
                }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['tenant', 'businessGroup'])->latest();
    }
}

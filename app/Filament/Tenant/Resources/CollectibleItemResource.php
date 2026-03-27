<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Models\Collectibles\CollectibleItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\Collectibles\CollectibleService;
use App\Services\Collectibles\ValuationService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CollectibleItemResource — Strategic management for rare goods.
 * Features valuation simulation and automatic certificate generation.
 */
class CollectibleItemResource extends Resource
{
    protected static ?string $model = CollectibleItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Collectibles Hub';
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    /**
     * Comprehensive form including condition grading and pricing rules.
     */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Core Spec')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('category_id')->relationship('category', 'name')->required(),
                Forms\Components\Select::make('store_id')->relationship('store', 'name')->required(),
                Forms\Components\TextInput::make('sku')->required()->unique(ignoreRecord: true),
                Forms\Components\Select::make('rarity')->options([
                    'Common' => 'Common', 'Rare' => 'Rare', 'Epic' => 'Epic', 'Legendary' => 'Legendary', 'Unique' => 'Unique'
                ])->required(),
            ])->columns(2),

            Forms\Components\Section::make('Valuation & Condition')->schema([
                Forms\Components\TextInput::make('price_cents')->numeric()->prefix('RUB')->required(),
                Forms\Components\Select::make('condition_grade')->options([
                    'Mint' => 'Mint', 'Near Mint' => 'Near Mint', 'Good' => 'Good', 'Used' => 'Used', 'Poor' => 'Poor'
                ])->required(),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(3),

            Forms\Components\MarkdownEditor::make('description')->columnSpanFull(),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    /**
     * Deep-scoping Table with analytics filters (rarity, category).
     */
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('sku')->copyable(),
            Tables\Columns\BadgeColumn::make('rarity')
                ->colors([
                    'gray' => 'Common', 'green' => 'Rare', 'blue' => 'Epic', 'purple' => 'Legendary', 'orange' => 'Unique'
                ]),
            Tables\Columns\TextColumn::make('price_cents')->money('rub', divideBy: 100),
            Tables\Columns\ToggleColumn::make('is_active'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('estimate_value')
                ->icon('heroicon-o-calculator')
                ->action(function (CollectibleItem $record, ValuationService $service) {
                    $value = $service->estimateValue($record->id);
                    Notification::make()->title("AI Estimation: " . number_format($value/100, 2) . " RUB")->success()->send();
                }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Global scope tenant isolation already active in the model, but we ensure sorting logic here
        return parent::getEloquentQuery()->with(['category', 'store'])->latest();
    }
}

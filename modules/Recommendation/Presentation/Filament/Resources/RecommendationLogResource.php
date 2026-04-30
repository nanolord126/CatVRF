<?php

declare(strict_types=1);

namespace Modules\Recommendation\Presentation\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Modules\BigData\Infrastructure\ClickHouse\ClickHouseClient;

final class RecommendationLogResource extends Resource
{
    protected static ?string $model = null;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Recommendation';
    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant_id')->sortable(),
                TextColumn::make('user_id')->sortable(),
                TextColumn::make('item_id')->sortable(),
                TextColumn::make('seller_id')->sortable(),
                TextColumn::make('vertical'),
                TextColumn::make('score')->numeric(),
                TextColumn::make('source'),
                TextColumn::make('scenario'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Recommendation\Presentation\Filament\Resources\RecommendationLogResource\Pages\ListRecommendationLogs::route('/'),
        ];
    }
}

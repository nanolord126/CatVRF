<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionTrendKeyword;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class FashionTrendKeywordResource extends Resource
{
    protected static ?string $model = FashionTrendKeyword::class;
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationGroup = 'Fashion Advanced';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tables\Columns\TextColumn::make('keyword'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('platform'),
                Tables\Columns\TextColumn::make('trend_score'),
                Tables\Columns\TextColumn::make('category'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('keyword')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('platform')->badge(),
                Tables\Columns\TextColumn::make('trend_score')->numeric(),
                Tables\Columns\TextColumn::make('velocity')->numeric(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('trend_score', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['hashtag' => 'Hashtag', 'keyword' => 'Keyword']),
                Tables\Filters\SelectFilter::make('platform')
                    ->options(['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'pinterest' => 'Pinterest', 'twitter' => 'Twitter']),
            ]);
    }
}

<?php declare(strict_types=1);

namespace App\Domains\Search\Filament\Resources;

use App\Domains\Search\Models\SearchIndex;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class SearchIndexResource extends Resource
{
    protected static ?string $model = SearchIndex::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Data';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('searchable_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('searchable_id')
                    ->numeric(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('ranking_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('type')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('searchable_type'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['searchable_type'])) {
                            $query->where('searchable_type', $data['searchable_type']);
                        }
                    }),
            ])
            ->defaultSort('ranking_score', 'desc')
            ->actions([]);
    }
}

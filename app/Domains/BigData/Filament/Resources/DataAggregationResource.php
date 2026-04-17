<?php declare(strict_types=1);

namespace App\Domains\BigData\Filament\Resources;

use App\Domains\BigData\Models\DataAggregation;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class DataAggregationResource extends Resource
{
    protected static ?string $model = DataAggregation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Data';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aggregation_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('aggregation_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('timestamp')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('source')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('source'),
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['source'])) {
                            $query->where('source', $data['source']);
                        }
                    }),
            ])
            ->defaultSort('timestamp', 'desc')
            ->actions([]);
    }
}

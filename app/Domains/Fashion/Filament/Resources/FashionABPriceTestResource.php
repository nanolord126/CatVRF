<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionABPriceTest;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class FashionABPriceTestResource extends Resource
{
    protected static ?string $model = FashionABPriceTest::class;
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Fashion Advanced';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tables\Columns\TextColumn::make('product_id'),
                Tables\Columns\TextColumn::make('control_price')->money('rub'),
                Tables\Columns\TextColumn::make('test_price')->money('rub'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('winner'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('product_id')->searchable(),
                Tables\Columns\TextColumn::make('control_price')->money('rub')->sortable(),
                Tables\Columns\TextColumn::make('test_price')->money('rub')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'completed' => 'success',
                        'draft' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('winner'),
                Tables\Columns\TextColumn::make('started_at')->dateTime(),
                Tables\Columns\TextColumn::make('completed_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'active' => 'Active', 'completed' => 'Completed']),
            ]);
    }
}

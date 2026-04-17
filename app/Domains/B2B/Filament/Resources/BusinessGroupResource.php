<?php declare(strict_types=1);

namespace App\Domains\B2B\Filament\Resources;

use App\Domains\B2B\Models\BusinessGroup;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BusinessGroupResource extends Resource
{
    protected static ?string $model = BusinessGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'B2B';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('inn')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->money('rub'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\TernaryFilter::make('is_verified'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}

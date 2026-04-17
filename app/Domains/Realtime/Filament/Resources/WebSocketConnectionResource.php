<?php declare(strict_types=1);

namespace App\Domains\Realtime\Filament\Resources;

use App\Domains\Realtime\Models\WebSocketConnection;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class WebSocketConnectionResource extends Resource
{
    protected static ?string $model = WebSocketConnection::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel')
                    ->searchable(),
                Tables\Columns\TextColumn::make('connection_id')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}

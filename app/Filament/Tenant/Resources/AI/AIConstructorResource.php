<?php

namespace App\Filament\Tenant\Resources\AI;

use App\Models\AI\InteriorDesignSession;
use App\Models\AI\BeautyTryOnSession;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms;
use Filament\Forms\Form;

class AIConstructorResource extends Resource
{
    protected static ?string $model = InteriorDesignSession::class;

    protected static ?string $navigationIcon = "heroicon-o-sparkles";
    protected static ?string $navigationGroup = "AI Ecosystem";
    protected static ?string $label = "AI Конструкторы";

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("id")->label("ID Сессии"),
                TextColumn::make("user.name")->label("Пользователь"),
                TextColumn::make("status")
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        "draft" => "gray",
                        "generated" => "info",
                        "ordered" => "success",
                        default => "gray",
                    }),
                TextColumn::make("total_amount")->money("RUB")->label("Сумма заказа"),
                TextColumn::make("created_at")->dateTime()->label("Дата"),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListAIConstructors::route("/"),
        ];
    }
}

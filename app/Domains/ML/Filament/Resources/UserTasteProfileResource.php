<?php declare(strict_types=1);

namespace App\Domains\ML\Filament\Resources;

use App\Domains\ML\Models\UserTasteProfile;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class UserTasteProfileResource extends Resource
{
    protected static ?string $model = UserTasteProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'ML';
    protected static ?int $navigationSort = 1;

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
                Tables\Columns\TextColumn::make('behavioral_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([]);
    }
}

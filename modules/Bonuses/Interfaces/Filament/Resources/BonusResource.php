<?php

declare(strict_types=1);

namespace Modules\Bonuses\Interfaces\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Bonuses\Domain\Entities\Bonus;
use App\Models\User;

final class BonusResource extends Resource
{
    protected static ?string $model = Bonus::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->label('Amount (in cents)'),
                Forms\Components\Select::make('type')
                    ->options([
                        'referral' => 'Referral',
                        'turnover' => 'Turnover',
                        'promo' => 'Promo',
                        'loyalty' => 'Loyalty',
                        'manual' => 'Manual',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('reason')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('expires_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('USD', true)->sortable(),
                Tables\Columns\TextColumn::make('type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reason')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \Modules\Bonuses\Interfaces\Filament\Resources\BonusResource\Pages\ListBonuses::class,
            'create' => \Modules\Bonuses\Interfaces\Filament\Resources\BonusResource\Pages\CreateBonus::class,
            'edit' => \Modules\Bonuses\Interfaces\Filament\Resources\BonusResource\Pages\EditBonus::class,
        ];
    }    
}

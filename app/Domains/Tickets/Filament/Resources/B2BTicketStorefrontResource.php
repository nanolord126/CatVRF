<?php declare(strict_types=1);

namespace App\Domains\Tickets\Filament\Resources;

use App\Domains\Tickets\Models\B2BTicketStorefront;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class B2BTicketStorefrontResource extends Resource
{
    protected static ?string $model = B2BTicketStorefront::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Tickets B2B';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('company_name')
                ->required()
                ->label('Название компании')
                ->maxLength(255),
            
            Forms\Components\TextInput::make('inn')
                ->required()
                ->label('ИНН')
                ->unique(),
            
            Forms\Components\Textarea::make('description')
                ->label('Описание')
                ->nullable(),
            
            Forms\Components\TextInput::make('wholesale_discount')
                ->label('Оптовая скидка (%)')
                ->numeric()
                ->nullable(),
            
            Forms\Components\TextInput::make('min_order_amount')
                ->label('Минимальная сумма заказа')
                ->numeric()
                ->default(50000),
            
            Forms\Components\Toggle::make('is_verified')
                ->label('Верифицирована')
                ->disabled(),
            
            Forms\Components\Toggle::make('is_active')
                ->label('Активна')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Компания')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('inn')
                    ->label('ИНН'),
                
                Tables\Columns\TextColumn::make('wholesale_discount')
                    ->label('Скидка'),
                
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Верифицирована')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_verified')
                    ->label('Статус верификации'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }
}


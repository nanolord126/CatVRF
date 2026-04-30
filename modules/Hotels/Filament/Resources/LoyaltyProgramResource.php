<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hotels\Infrastructure\Models\LoyaltyProgramModel;

final class LoyaltyProgramResource extends Resource
{
    protected static ?string $model = LoyaltyProgramModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Программы лояльности';
    protected static ?string $navigationGroup = 'Отели';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->relationship('venue', 'name')
                            ->searchable()
                            ->required()
                            ->label('Отель'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Название'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание'),
                        Forms\Components\Select::make('level')
                            ->options([
                                'bronze' => 'Бронза',
                                'silver' => 'Серебро',
                                'gold' => 'Золото',
                                'platinum' => 'Платина',
                                'corporate' => 'Корпоративный',
                            ])
                            ->required()
                            ->label('Уровень'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Баллы')
                    ->schema([
                        Forms\Components\TextInput::make('points_per_night')
                            ->numeric()
                            ->default(10)
                            ->required()
                            ->label('Баллов за ночь'),
                        Forms\Components\TextInput::make('points_to_rubles_rate')
                            ->numeric()
                            ->default(0.01)
                            ->required()
                            ->label('Курс (баллов → рубли)'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Период действия')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('Действует с'),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Действует до'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активна'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Преимущества')
                    ->schema([
                        Forms\Components\KeyValue::make('benefits')
                            ->label('Преимущества')
                            ->keyLabel('Название')
                            ->valueLabel('Значение')
                            ->addActionLabel('Добавить преимущество'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Отель')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('level')
                    ->label('Уровень')
                    ->colors([
                        'warning' => 'bronze',
                        'gray' => 'silver',
                        'yellow' => 'gold',
                        'purple' => 'platinum',
                        'blue' => 'corporate',
                    ]),
                Tables\Columns\TextColumn::make('points_per_night')
                    ->label('Баллов/ночь'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активна'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'bronze' => 'Бронза',
                        'silver' => 'Серебро',
                        'gold' => 'Золото',
                        'platinum' => 'Платина',
                        'corporate' => 'Корпоративный',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активна'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Hotels\Filament\Resources\LoyaltyProgramResource\Pages\ListLoyaltyPrograms::route('/'),
            'create' => \Modules\Hotels\Filament\Resources\LoyaltyProgramResource\Pages\CreateLoyaltyProgram::route('/create'),
            'view' => \Modules\Hotels\Filament\Resources\LoyaltyProgramResource\Pages\ViewLoyaltyProgram::route('/{record}'),
            'edit' => \Modules\Hotels\Filament\Resources\LoyaltyProgramResource\Pages\EditLoyaltyProgram::route('/{record}/edit'),
        ];
    }
}

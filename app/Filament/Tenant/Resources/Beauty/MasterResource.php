<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\Master;
use App\Filament\Tenant\Resources\Beauty\MasterResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * MasterResource — Filament-ресурс мастеров (B2B Tenant Panel).
 */
final class MasterResource extends Resource
{
    protected static ?string $model = Master::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $navigationLabel = 'Мастера';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('salon_id')
                        ->label('Салон')
                        ->relationship('salon', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('full_name')
                        ->label('ФИО')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('specialization')
                        ->label('Специализация')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('experience_years')
                        ->label('Опыт (лет)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(60),

                    Forms\Components\Textarea::make('bio')
                        ->label('О мастере')
                        ->maxLength(3000)
                        ->rows(4)
                        ->columnSpan(2),
                ]),

            Forms\Components\Section::make('Контакты')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(30),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                ]),

            Forms\Components\Section::make('Фото и услуги')
                ->schema([
                    Forms\Components\FileUpload::make('avatar_url')
                        ->label('Аватар')
                        ->image()
                        ->disk('s3')
                        ->directory('beauty/masters/avatars')
                        ->maxSize(3072),

                    Forms\Components\Select::make('services')
                        ->label('Услуги')
                        ->relationship('services', 'name')
                        ->multiple()
                        ->searchable()
                        ->preload(),
                ]),

            Forms\Components\Section::make('Статус')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('salon.name')
                    ->label('Салон')
                    ->sortable(),

                Tables\Columns\TextColumn::make('specialization')
                    ->label('Специализация')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2)),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Услуги')
                    ->counts('services'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('full_name')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMasters::route('/'),
            'create' => Pages\CreateMaster::route('/create'),
            'edit'   => Pages\EditMaster::route('/{record}/edit'),
        ];
    }
}

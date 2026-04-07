<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty;

use App\Domains\Beauty\Models\Salon;
use App\Filament\Tenant\Resources\Beauty\SalonResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * SalonResource — Filament-ресурс для управления салонами (B2B Tenant Panel).
 *
 * CRUD: создание, редактирование, деактивация.
 * Поля: name, address, lat/lon, phone, email, working_hours, tags.
 * Связи: masters, services.
 */
final class SalonResource extends Resource
{
    protected static ?string $model = Salon::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $navigationLabel = 'Салоны';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->maxLength(5000)
                        ->rows(4)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('address')
                        ->label('Адрес')
                        ->required()
                        ->maxLength(500)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('lat')
                        ->label('Широта')
                        ->required()
                        ->numeric()
                        ->step(0.00000001),

                    Forms\Components\TextInput::make('lon')
                        ->label('Долгота')
                        ->required()
                        ->numeric()
                        ->step(0.00000001),
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

            Forms\Components\Section::make('Логотип и теги')
                ->schema([
                    Forms\Components\FileUpload::make('logo_url')
                        ->label('Логотип')
                        ->image()
                        ->disk('s3')
                        ->directory('beauty/salons/logos')
                        ->maxSize(5120)
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('1:1')
                        ->imageResizeTargetWidth('400')
                        ->imageResizeTargetHeight('400'),

                    Forms\Components\TagsInput::make('tags')
                        ->label('Теги')
                        ->placeholder('Добавьте тег...'),
                ]),

            Forms\Components\Section::make('Статус')
                ->schema([
                    Forms\Components\Toggle::make('is_verified')
                        ->label('Верифицирован')
                        ->default(false),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'active'   => 'Активный',
                            'inactive' => 'Неактивный',
                            'blocked'  => 'Заблокирован',
                        ])
                        ->default('active')
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('')
                    ->circular()
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\TextColumn::make('masters_count')
                    ->label('Мастера')
                    ->counts('masters')
                    ->sortable(),

                Tables\Columns\TextColumn::make('services_count')
                    ->label('Услуги')
                    ->counts('services')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1)),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger'  => 'blocked',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active'   => 'Активный',
                        'inactive' => 'Неактивный',
                        'blocked'  => 'Заблокирован',
                    ]),
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
            'index'  => Pages\ListSalons::route('/'),
            'create' => Pages\CreateSalon::route('/create'),
            'edit'   => Pages\EditSalon::route('/{record}/edit'),
        ];
    }
}

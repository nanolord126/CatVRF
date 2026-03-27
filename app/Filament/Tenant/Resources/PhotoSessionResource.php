<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Photography\Models\PhotoSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — PHOTO SESSION RESOURCE
 */
final class PhotoSessionResource extends Resource
{
    protected static ?string $model = PhotoSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    
    protected static ?string $navigationGroup = 'Photography';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Детали Фотосессии')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название услуги')
                            ->required(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (мин)')
                            ->numeric()
                            ->default(60)
                            ->required(),

                        Forms\Components\TextInput::make('price_kopecks')
                            ->label('Цена (в копейках)')
                            ->numeric()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Теги категории')
                            ->placeholder('портрет, студийная, пленэр'),
                    ]),

                Forms\Components\Section::make('Системные')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('uuid')->default(fn() => (string) Str::uuid())->disabled(),
                        Forms\Components\TextInput::make('correlation_id')->default(fn() => (string) Str::uuid())->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Длительность'),
                Tables\Columns\TextColumn::make('price_kopecks')->label('Цена')->money('RUB', divideBy: 100),
                Tables\Columns\ToggleColumn::make('is_active')->label('Работает'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\PhotoSessionResource\Pages\ListPhotoSessions::route('/'),
            'create' => \App\Filament\Tenant\Resources\PhotoSessionResource\Pages\CreatePhotoSession::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\PhotoSessionResource\Pages\EditPhotoSession::route('/{record}/edit'),
        ];
    }
}

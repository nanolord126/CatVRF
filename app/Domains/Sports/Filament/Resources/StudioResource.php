<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use Filament\Resources\Resource;

final class StudioResource extends Resource
{

    protected static ?string $model = Studio::class;
        protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
        protected static ?string $navigationLabel = 'Студии';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')->label('Название')->required(),
                        Forms\Components\RichEditor::make('description')->label('Описание')->required(),
                        Forms\Components\TextInput::make('address')->label('Адрес')->required(),
                        Forms\Components\TextInput::make('phone')->label('Телефон'),
                        Forms\Components\TextInput::make('website')->label('Сайт')->url(),
                    ]),
                Forms\Components\Section::make('Параметры')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')->label('Проверена'),
                        Forms\Components\TextInput::make('rating')->label('Рейтинг')->disabled(),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->label('Студия')->sortable()->searchable(),
                    Tables\Columns\TextColumn::make('address')->label('Адрес')->sortable(),
                    Tables\Columns\TextColumn::make('trainer_count')->label('Тренеров'),
                    Tables\Columns\TextColumn::make('member_count')->label('Членов'),
                    Tables\Columns\TextColumn::make('rating')->label('Рейтинг')->sortable(),
                    Tables\Columns\IconColumn::make('is_verified')->label('Проверена')->boolean(),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_verified')->label('Проверённые'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\Sports\Filament\Resources\StudioResource\Pages\ListStudios::route('/'),
                'create' => \App\Domains\Sports\Filament\Resources\StudioResource\Pages\CreateStudio::route('/create'),
                'edit' => \App\Domains\Sports\Filament\Resources\StudioResource\Pages\EditStudio::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', filament()->getTenant()?->id);
        }
}

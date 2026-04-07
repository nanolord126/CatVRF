<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Filament\Resources;

use Filament\Resources\Resource;

final class EntertainerResource extends Resource
{

    protected static ?string $model = Entertainer::class;

        protected static ?string $navigationIcon = 'heroicon-o-user-group';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form
                ->schema([
                    Forms\Components\Select::make('venue_id')
                        ->relationship('venue', 'name')
                        ->nullable(),
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->nullable(),
                    Forms\Components\TextInput::make('full_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\RichEditor::make('bio')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('hourly_rate')
                        ->numeric(),
                    Forms\Components\Toggle::make('is_verified')
                        ->default(false),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('full_name')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('venue.name'),
                    Tables\Columns\TextColumn::make('event_count'),
                    Tables\Columns\TextColumn::make('rating')
                        ->sortable(),
                    Tables\Columns\IconColumn::make('is_verified')
                        ->boolean(),
                ])
                ->filters([
                    Tables\Filters\TrashedFilter::make(),
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

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainerResource\Pages\ListEntertainers::class,
                'create' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainerResource\Pages\CreateEntertainer::class,
                'edit' => \App\Domains\EventPlanning\Entertainment\Filament\Resources\EntertainerResource\Pages\EditEntertainer::class,
            ];
        }
}

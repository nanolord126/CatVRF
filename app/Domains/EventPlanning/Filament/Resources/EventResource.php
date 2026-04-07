<?php

declare(strict_types=1);

namespace App\Domains\EventPlanning\Filament\Resources;

use App\Domains\EventPlanning\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament Resource: Event.
 *
 * CANON 2026 — Layer 9: Filament admin panel resource.
 * Tenant-scoped через global scope.
 */
final class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'EventPlanning';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255),
            Forms\Components\Textarea::make('description')
                ->label('Описание')
                ->maxLength(5000),
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options([
                    'active'   => 'Активный',
                    'draft'    => 'Черновик',
                    'archived' => 'Архив',
                ])
                ->default('active'),
            Forms\Components\TagsInput::make('tags')
                ->label('Теги'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Активный',
                        'draft'    => 'Черновик',
                        'archived' => 'Архив',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Domains\EventPlanning\Filament\Resources\EventResource\Pages\ListEvents::route('/'),
            'create' => \App\Domains\EventPlanning\Filament\Resources\EventResource\Pages\CreateEvent::route('/create'),
            'edit'   => \App\Domains\EventPlanning\Filament\Resources\EventResource\Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}

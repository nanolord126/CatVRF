<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\EventPlanning\Filament\Resources;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Domains\EventPlanning\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseOptimizedResource;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\EventPlanning\Filament\Resources\EventResource\Pages\CreateEvent;
use App\Domains\EventPlanning\Filament\Resources\EventResource\Pages\EditEvent;
use App\Domains\EventPlanning\Filament\Resources\EventResource\Pages\ListEvents;

/**
 * Filament Resource: Event.
 *
 * CANON 2026 — Layer 9: Filament admin panel resource.
 * Tenant-scoped через global scope.
 */
final class EventResource extends BaseOptimizedResource
{
    protected static ?string $model = $this->eventDispatcher->class;

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
            'index'  => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit'   => EditEvent::route('/{record}/edit'),
        ];
    }

    /**
     * Relations to eager load for EventPlanning
     */
    protected static function getEagerLoading(): array
    {
        return [];
    }
}

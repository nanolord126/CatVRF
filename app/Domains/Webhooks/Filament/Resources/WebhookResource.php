<?php declare(strict_types=1);

namespace App\Domains\Webhooks\Filament\Resources;

use App\Domains\Webhooks\Models\Webhook;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class WebhookResource extends Resource
{
    protected static ?string $model = Webhook::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Integrations';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('url')
                    ->required()
                    ->url()
                    ->maxLength(500),
                Forms\Components\TagsInput::make('events')
                    ->required()
                    ->placeholder('Add event type'),
                Forms\Components\TextInput::make('secret')
                    ->password()
                    ->minLength(32),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
                Forms\Components\TextInput::make('retry_count')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10)
                    ->default(3),
                Forms\Components\TextInput::make('timeout')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(120)
                    ->default(30),
                Forms\Components\KeyValue::make('headers'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TagsColumn::make('events')
                    ->separator(','),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

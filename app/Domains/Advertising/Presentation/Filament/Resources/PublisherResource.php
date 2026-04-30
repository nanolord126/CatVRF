<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources;

use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentPublisher;
use App\Domains\Advertising\Presentation\Filament\Resources\PublisherResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PublisherResource extends Resource
{
    protected static ?string $model = EloquentPublisher::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Advertising';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Publisher Name'),

                Forms\Components\TextInput::make('website_url')
                    ->required()
                    ->url()
                    ->maxLength(500)
                    ->label('Website URL'),

                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ])
                    ->default('pending')
                    ->required()
                    ->label('Status'),

                Forms\Components\TextInput::make('commission_rate')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(0.5)
                    ->step(0.0001)
                    ->default(0.15)
                    ->label('Commission Rate'),

                Forms\Components\TextInput::make('payout_threshold')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(1000000)
                    ->label('Payout Threshold (kopeks)'),

                Forms\Components\TextInput::make('api_key')
                    ->disabled()
                    ->label('API Key'),

                Forms\Components\TextInput::make('webhook_url')
                    ->url()
                    ->maxLength(500)
                    ->label('Webhook URL'),

                Forms\Components\Select::make('integration_type')
                    ->options([
                        'direct' => 'Direct',
                        'ssp' => 'SSP',
                        'dsp' => 'DSP',
                    ])
                    ->default('direct')
                    ->required()
                    ->label('Integration Type'),

                Forms\Components\DateTimePicker::make('verified_at')
                    ->label('Verified At'),

                Forms\Components\DateTimePicker::make('last_payout_at')
                    ->label('Last Payout At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Name')
                    ->wrap(),

                Tables\Columns\TextColumn::make('website_url')
                    ->url(fn ($state) => $state)
                    ->limit(30)
                    ->label('Website'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'suspended' => 'danger',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->percentage()
                    ->sortable()
                    ->label('Commission'),

                Tables\Columns\TextColumn::make('payout_threshold')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Payout Threshold'),

                Tables\Columns\TextColumn::make('integration_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'direct' => 'primary',
                        'ssp' => 'info',
                        'dsp' => 'success',
                    })
                    ->label('Integration'),

                Tables\Columns\TextColumn::make('verified_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Verified At'),

                Tables\Columns\TextColumn::make('last_payout_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Payout'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),

                Tables\Filters\SelectFilter::make('integration_type')
                    ->options([
                        'direct' => 'Direct',
                        'ssp' => 'SSP',
                        'dsp' => 'DSP',
                    ]),

                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active'))
                    ->label('Active Publishers'),
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublishers::route('/'),
            'create' => Pages\CreatePublisher::route('/create'),
            'edit' => Pages\EditPublisher::route('/{record}/edit'),
        ];
    }
}

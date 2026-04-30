<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources;

use App\Domains\Advertising\Infrastructure\Persistence\Eloquent\Models\EloquentAdShort;
use App\Domains\Advertising\Presentation\Filament\Resources\AdShortResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AdShortResource extends Resource
{
    protected static ?string $model = EloquentAdShort::class;

    protected static ?string $navigationIcon = 'heroicon-o-film';

    protected static ?string $navigationGroup = 'Advertising';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->label('Title'),

                Forms\Components\TextInput::make('video_url')
                    ->required()
                    ->url()
                    ->maxLength(500)
                    ->label('Video URL'),

                Forms\Components\TextInput::make('thumbnail_url')
                    ->required()
                    ->url()
                    ->maxLength(500)
                    ->label('Thumbnail URL'),

                Forms\Components\TextInput::make('duration_seconds')
                    ->required()
                    ->numeric()
                    ->minValue(15)
                    ->maxValue(60)
                    ->label('Duration (seconds)'),

                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('draft')
                    ->required()
                    ->label('Status'),

                Forms\Components\DateTimePicker::make('start_at')
                    ->required()
                    ->label('Start At'),

                Forms\Components\DateTimePicker::make('end_at')
                    ->required()
                    ->label('End At'),

                Forms\Components\TextInput::make('budget')
                    ->required()
                    ->numeric()
                    ->minValue(10000)
                    ->label('Budget (kopeks)'),

                Forms\Components\TextInput::make('spent')
                    ->numeric()
                    ->default(0)
                    ->label('Spent (kopeks)')
                    ->disabled(),

                Forms\Components\Select::make('pricing_model')
                    ->options([
                        'cpm' => 'CPM',
                        'cpc' => 'CPC',
                        'cpa' => 'CPA',
                        'cpv' => 'CPV',
                    ])
                    ->default('cpm')
                    ->required()
                    ->label('Pricing Model'),

                Forms\Components\Textarea::make('targeting_criteria')
                    ->rows(3)
                    ->label('Targeting Criteria (JSON)'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->label('Title')
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_review' => 'warning',
                        'active' => 'success',
                        'paused' => 'info',
                        'completed' => 'primary',
                        'rejected' => 'danger',
                        'cancelled' => 'danger',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('pricing_model')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cpm' => 'primary',
                        'cpc' => 'secondary',
                        'cpa' => 'info',
                        'cpv' => 'success',
                    })
                    ->label('Pricing Model'),

                Tables\Columns\TextColumn::make('duration_seconds')
                    ->numeric()
                    ->sortable()
                    ->label('Duration (s)'),

                Tables\Columns\TextColumn::make('budget')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Budget'),

                Tables\Columns\TextColumn::make('spent')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Spent'),

                Tables\Columns\TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Start At'),

                Tables\Columns\TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable()
                    ->label('End At'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Created At')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('pricing_model')
                    ->options([
                        'cpm' => 'CPM',
                        'cpc' => 'CPC',
                        'cpa' => 'CPA',
                        'cpv' => 'CPV',
                    ]),

                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active'))
                    ->label('Active Shorts'),
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
            'index' => Pages\ListAdShorts::route('/'),
            'create' => Pages\CreateAdShort::route('/create'),
            'edit' => Pages\EditAdShort::route('/{record}/edit'),
        ];
    }
}

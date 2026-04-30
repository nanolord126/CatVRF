<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources;

use App\Domains\Advertising\Models\AdCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AdCampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Advertising';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Campaign Name'),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->label('Description'),

                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'completed' => 'Completed',
                    ])
                    ->default('draft')
                    ->required()
                    ->label('Status'),

                Forms\Components\DateTimePicker::make('start_date')
                    ->required()
                    ->label('Start Date'),

                Forms\Components\DateTimePicker::make('end_date')
                    ->label('End Date'),

                Forms\Components\TextInput::make('budget')
                    ->numeric()
                    ->label('Budget'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Campaign Name')
                    ->wrap(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'paused' => 'warning',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                    })
                    ->label('Status'),

                Tables\Columns\TextColumn::make('pricing_model')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cpm' => 'primary',
                        'cpc' => 'secondary',
                        'cpa' => 'info',
                        'flat' => 'success',
                    })
                    ->label('Pricing Model'),

                Tables\Columns\TextColumn::make('budget')
                    ->money('RUB')
                    ->sortable()
                    ->label('Budget'),

                Tables\Columns\TextColumn::make('spent')
                    ->money('RUB')
                    ->sortable()
                    ->label('Spent'),

                Tables\Columns\TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Start Date'),

                Tables\Columns\TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable()
                    ->label('End Date'),

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
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('pricing_model')
                    ->options([
                        'cpm' => 'CPM',
                        'cpc' => 'CPC',
                        'cpa' => 'CPA',
                        'flat' => 'Flat Rate',
                    ]),

                Tables\Filters\Filter::make('active_campaigns')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'active'))
                    ->label('Active Campaigns'),
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
            'index' => Pages\ListAdCampaigns::route('/'),
            'create' => Pages\CreateAdCampaign::route('/create'),
            'edit' => Pages\EditAdCampaign::route('/{record}/edit'),
        ];
    }
}

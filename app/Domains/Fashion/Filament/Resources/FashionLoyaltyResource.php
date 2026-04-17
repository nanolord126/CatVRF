<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\FashionLoyaltyResource\Pages;

final class FashionLoyaltyResource extends Resource
{
    protected static ?string $model = \App\Models\FashionLoyaltyPoint::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Fashion AI';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Loyalty Information')
                    ->schema([
                        Forms\Components\TextInput::make('total_points')
                            ->numeric()
                            ->required()
                            ->default(0),
                        Forms\Components\Select::make('tier')
                            ->options([
                                'standard' => 'Standard',
                                'bronze' => 'Bronze',
                                'silver' => 'Silver',
                                'gold' => 'Gold',
                                'platinum' => 'Platinum',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('last_earned_at'),
                        Forms\Components\DateTimePicker::make('last_redeemed_at'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_points')
                    ->label('Points')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\BadgeColumn::make('tier')
                    ->colors([
                        'gray' => 'standard',
                        'orange' => 'bronze',
                        'gray' => 'silver',
                        'yellow' => 'gold',
                        'purple' => 'platinum',
                    ]),
                Tables\Columns\TextColumn::make('last_earned_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tier')
                    ->options([
                        'standard' => 'Standard',
                        'bronze' => 'Bronze',
                        'silver' => 'Silver',
                        'gold' => 'Gold',
                        'platinum' => 'Platinum',
                    ]),
                Tables\Filters\Filter::make('high_value')
                    ->query(fn (Builder $query): Builder => $query->where('total_points', '>=', 5000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListFashionLoyalty::route('/'),
            'create' => Pages\CreateFashionLoyalty::route('/create'),
            'view' => Pages\ViewFashionLoyalty::route('/{record}'),
            'edit' => Pages\EditFashionLoyalty::route('/{record}/edit'),
        ];
    }
}

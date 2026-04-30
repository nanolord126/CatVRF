<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources;

use Modules\Flowers\Infrastructure\Models\FloristModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class FloristResource extends Resource
{
    protected static ?string $model = FloristModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Flowers CRM';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Florist Information')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Skills & Specialization')
                    ->schema([
                        Forms\Components\TextInput::make('specialization')
                            ->maxLength(255)
                            ->helperText('e.g., Wedding bouquets, Corporate arrangements'),
                        Forms\Components\Textarea::make('skills')
                            ->rows(3)
                            ->helperText('List of skills and certifications'),
                        Forms\Components\TextInput::make('hourly_rate')
                            ->numeric()
                            ->prefix('₽'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Working Hours')
                    ->schema([
                        Forms\Components\KeyValue::make('working_hours')
                            ->keyLabel('Day')
                            ->valueLabel('Hours (e.g., 09:00-18:00)')
                            ->default([
                                'monday' => '09:00-18:00',
                                'tuesday' => '09:00-18:00',
                                'wednesday' => '09:00-18:00',
                                'thursday' => '09:00-18:00',
                                'friday' => '09:00-18:00',
                                'saturday' => '10:00-16:00',
                                'sunday' => 'closed',
                            ]),
                    ]),

                Forms\Components\Section::make('Performance')
                    ->schema([
                        Forms\Components\TextInput::make('orders_completed')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('average_rating')
                            ->numeric()
                            ->step(0.1)
                            ->minValue(0)
                            ->maxValue(5)
                            ->default(0)
                            ->disabled(),
                        Forms\Components\TextInput::make('total_rating_count')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->label('Available for new orders')
                            ->default(true),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active employee')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('specialization')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('orders_completed')
                    ->sortable()
                    ->label('Completed'),
                TextColumn::make('average_rating')
                    ->sortable()
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' / 5'),
                TextColumn::make('total_rating_count')
                    ->sortable()
                    ->label('Reviews')
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('is_available')
                    ->boolean()
                    ->label('Available'),
                BadgeColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_available')
                    ->query(fn (Builder $query): Builder => $query->where('is_available', true)),
                Tables\Filters\Filter::make('top_rated')
                    ->query(fn (Builder $query): Builder => $query->where('average_rating', '>=', 4.5)->where('total_rating_count', '>=', 10)),
                Tables\Filters\Filter::make('experienced')
                    ->query(fn (Builder $query): Builder => $query->where('orders_completed', '>=', 50)),
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
            'orders' => Tables\Relations\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Flowers\Filament\Resources\FloristResource\Pages\ListFlorists::route('/'),
            'create' => \Modules\Flowers\Filament\Resources\FloristResource\Pages\CreateFlorist::route('/create'),
            'view' => \Modules\Flowers\Filament\Resources\FloristResource\Pages\ViewFlorist::route('/{record}'),
            'edit' => \Modules\Flowers\Filament\Resources\FloristResource\Pages\EditFlorist::route('/{record}/edit'),
        ];
    }
}

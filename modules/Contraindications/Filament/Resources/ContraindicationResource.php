<?php

declare(strict_types=1);

namespace Modules\Contraindications\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Contraindications\Infrastructure\Models\ContraindicationModel;

final class ContraindicationResource extends Resource
{
    protected static ?string $model = ContraindicationModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Health & Safety';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('User')
                    ->requiredWithout('pet_id'),
                Forms\Components\Select::make('pet_id')
                    ->relationship('pet', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Pet')
                    ->requiredWithout('user_id'),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Contraindication Name'),
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                Forms\Components\Select::make('severity')
                    ->options([
                        'low' => 'Low',
                        'moderate' => 'Moderate',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ])
                    ->required()
                    ->default('moderate'),
                Forms\Components\CheckboxList::make('scopes')
                    ->options([
                        'cosmetology' => 'Cosmetology',
                        'food' => 'Food',
                        'medical' => 'Medical',
                        'grooming' => 'Grooming',
                        'fitness' => 'Fitness',
                    ])
                    ->label('Applicable Scopes')
                    ->helperText('Leave empty to apply to all scopes'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('pet.name')
                    ->label('Pet')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Contraindication'),
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'moderate',
                        'danger' => 'high',
                        'red' => 'critical',
                    ]),
                Tables\Columns\TextColumn::make('scopes')
                    ->badge()
                    ->separator(','),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->options([
                        'low' => 'Low',
                        'moderate' => 'Moderate',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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

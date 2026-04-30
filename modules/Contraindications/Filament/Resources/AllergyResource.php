<?php

declare(strict_types=1);

namespace Modules\Contraindications\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Contraindications\Domain\Enums\AllergySeverity;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use Modules\Contraindications\Infrastructure\Models\AllergyModel;

final class AllergyResource extends Resource
{
    protected static ?string $model = AllergyModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

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
                    ->label('Allergen Name'),
                Forms\Components\Select::make('severity')
                    ->options([
                        'mild' => 'Mild',
                        'moderate' => 'Moderate',
                        'severe' => 'Severe',
                        'life_threatening' => 'Life Threatening',
                    ])
                    ->required()
                    ->default('moderate'),
                Forms\Components\Textarea::make('reaction')
                    ->label('Reaction Description')
                    ->rows(3),
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
                    ->label('Allergen'),
                Tables\Columns\BadgeColumn::make('severity')
                    ->colors([
                        'gray' => 'mild',
                        'warning' => 'moderate',
                        'danger' => 'severe',
                        'red' => 'life_threatening',
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
                        'mild' => 'Mild',
                        'moderate' => 'Moderate',
                        'severe' => 'Severe',
                        'life_threatening' => 'Life Threatening',
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

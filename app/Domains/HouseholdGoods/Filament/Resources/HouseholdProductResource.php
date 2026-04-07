<?php declare(strict_types=1);

namespace App\Domains\HouseholdGoods\Filament\Resources;


use App\Domains\HouseholdGoods\Models\HouseholdProduct;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class HouseholdProductResource extends Resource
{
    protected static ?string $model = HouseholdProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'HouseholdGoods';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'draft' => 'Draft',
                ])
                ->default('active'),

            Forms\Components\Hidden::make('tenant_id')
                ->default(fn (): ?int => function_exists('tenant') && tenant() ? tenant()->id : null),

            Forms\Components\Hidden::make('correlation_id')
                ->default(fn (): string => \Illuminate\Support\Str::uuid()->toString()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'inactive' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'draft' => 'Draft',
                    ]),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource\Pages\ListHouseholdProducts::route('/'),
            'create' => \App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource\Pages\CreateHouseholdProduct::route('/create'),
            'edit' => \App\Domains\HouseholdGoods\Filament\Resources\HouseholdProductResource\Pages\EditHouseholdProduct::route('/{record}/edit'),
        ];
    }
}
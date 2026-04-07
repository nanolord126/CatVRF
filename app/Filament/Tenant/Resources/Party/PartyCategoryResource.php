<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party;

final class PartyCategoryResource extends Resource
{

    protected static ?string $model = PartyCategory::class;

        protected static ?string $navigationIcon = 'heroicon-o-tag';
        protected static ?string $navigationGroup = 'Party Supplies';
        protected static ?int $navigationSort = 1;

        public static function form(Form $form): Form
        {
            return $form->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Active for Marketplace')
                    ->default(true),

                TagsInput::make('tags')
                    ->placeholder('Add event tags (e.g., kids, outdoor, disco)')
                    ->columnSpanFull(),

                TextInput::make('correlation_id')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn ($record) => $record !== null),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('slug')
                        ->copyable()
                        ->color('gray'),

                    IconColumn::make('is_active')
                        ->boolean()
                        ->label('Status'),

                    TextColumn::make('products_count')
                        ->counts('products')
                        ->label('Total Products'),

                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Filter::make('active_only')
                        ->query(fn (Builder $query) => $query->where('is_active', true)),
                ])
                ->actions([
                    EditAction::make(),
                ])
                ->bulkActions([
                    DeleteBulkAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withCount('products')
                ->orderBy('name');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Party\Pages\ListPartyCategories::route('/'),
                'create' => \App\Filament\Tenant\Resources\Party\Pages\CreatePartyCategory::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Party\Pages\EditPartyCategory::route('/{record}/edit'),
            ];
        }
}

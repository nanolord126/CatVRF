<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology;

final class PsychologistResource extends Resource
{

    protected static ?string $model = Psychologist::class;

        protected static ?string $navigationIcon = 'heroicon-o-user-group';
        protected static ?string $navigationGroup = 'Psychological Services';
        protected static ?int $navigationSort = 1;

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Core Information')
                    ->description('Main therapist details and career data')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('clinic_id')
                            ->relationship('clinic', 'name')
                            ->required(),
                        Forms\Components\TextInput::make('full_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('specialization')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('experience_years') // В миграции int, тут юзаем число
                            ->hidden(),
                        Forms\Components\TextInput::make('experience_years')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('base_price_per_hour')
                            ->numeric()
                            ->prefix('RUB')
                            ->step(100)
                            ->required(),
                        Forms\Components\Toggle::make('is_available')
                            ->label('Accepting new clients')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Expertise & Knowledge')
                    ->description('Methods of therapy and education history')
                    ->columns(2)
                    ->schema([
                        Forms\Components\CheckboxList::make('therapy_types')
                            ->options([
                                'cbt' => 'Cognitive Behavioral Therapy (CBT)',
                                'gestalt' => 'Gestalt Therapy',
                                'existential' => 'Existential Therapy',
                                'psychoanalysis' => 'Psychoanalysis',
                                'art' => 'Art Therapy',
                                'family' => 'Family Therapy',
                            ])
                            ->required()
                            ->columns(2),
                        Forms\Components\Textarea::make('biography')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('education')
                            ->schema([
                                Forms\Components\TextInput::make('institution')->required(),
                                Forms\Components\TextInput::make('degree')->required(),
                                Forms\Components\TextInput::make('year')->numeric()->required(),
                            ])
                            ->columnSpanFull()
                            ->grid(2)
                            ->default([]),
                    ]),

                Forms\Components\Section::make('Technical Metadata')
                    ->collapsed()
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Custom AI Settings')
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->placeholder('Add searchable tags')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('uuid')
                            ->content(fn ($record) => $record?->uuid ?? 'N/A'),
                        Forms\Components\Placeholder::make('correlation_id')
                            ->content(fn ($record) => $record?->correlation_id ?? 'N/A'),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('full_name')
                        ->searchable()
                        ->sortable()
                        ->description(fn (Psychologist $record) => $record->specialization),
                    Tables\Columns\TextColumn::make('clinic.name')
                        ->sortable()
                        ->badge(),
                    Tables\Columns\TextColumn::make('base_price_per_hour')
                        ->money('RUB')
                        ->sortable()
                        ->color('success'),
                    Tables\Columns\TextColumn::make('rating')
                        ->numeric(decimalPlaces: 1)
                        ->avg('reviews', 'rating')
                        ->sortable()
                        ->icon('heroicon-s-star')
                        ->color('warning'),
                    Tables\Columns\IconColumn::make('is_available')
                        ->boolean()
                        ->label('Available'),
                    Tables\Columns\TextColumn::make('bookings_count')
                        ->counts('bookings')
                        ->label('Total Sessions')
                        ->badge(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('clinic')
                        ->relationship('clinic', 'name'),
                    Tables\Filters\TernaryFilter::make('is_available'),
                    Tables\Filters\Filter::make('experienced')
                        ->query(fn (Builder $query) => $query->where('experience_years', '>=', 10))
                        ->label('Experience 10+ years'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
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
                // ServicesRelationManager::class
            ];
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages\ListPsychologists::route('/'),
                'create' => \App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages\CreatePsychologist::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Psychology\PsychologistResource\Pages\EditPsychologist::route('/{record}/edit'),
            ];
        }
}

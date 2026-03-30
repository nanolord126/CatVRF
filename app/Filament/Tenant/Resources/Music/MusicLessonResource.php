<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicLessonResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = MusicLesson::class;

        protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

        protected static ?string $navigationGroup = 'Music & Instruments';

        protected static ?string $modelLabel = 'Lesson';

        protected static ?string $pluralModelLabel = 'Lessons';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Lesson Information')
                        ->description('Primary class and instructor details')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Lesson Title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g., Intro to Jazz Piano'),

                            Forms\Components\Select::make('store_id')
                                ->label('Offered by Store')
                                ->options(MusicStore::pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Forms\Components\TextInput::make('instructor_name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Full name of the teacher'),

                            Forms\Components\Select::make('instrument_id')
                                ->label('Primary Instrument (Optional)')
                                ->options(MusicInstrument::pluck('name', 'id'))
                                ->searchable()
                                ->required(false),

                            Forms\Components\Textarea::make('description')
                                ->maxLength(65535)
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Schedule & Pricing')
                        ->description('Course logistics and costs')
                        ->schema([
                            Forms\Components\TextInput::make('hourly_rate')
                                ->label('Hourly Rate (kopeks)')
                                ->numeric()
                                ->required()
                                ->default(100000)
                                ->suffix('коп/час'),

                            Forms\Components\TextInput::make('student_capacity')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->label('Max Students'),

                            Forms\Components\Select::make('difficulty_level')
                                ->label('Expertise Required')
                                ->options([
                                    'beginner' => 'Beginner',
                                    'intermediate' => 'Intermediate',
                                    'advanced' => 'Advanced',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('duration_minutes')
                                ->numeric()
                                ->default(60)
                                ->required()
                                ->suffix('min'),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Status Active')
                                ->default(true),

                            Forms\Components\KeyValue::make('tags')
                                ->label('Custom Metadata')
                                ->required(false),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->description(fn (MusicLesson $record): string => Str::limit($record->instructor_name, 30)),

                    Tables\Columns\TextColumn::make('store.name')
                        ->label('Store')
                        ->sortable()
                        ->searchable(),

                    Tables\Columns\TextColumn::make('hourly_rate')
                        ->money('RUB', divisor: 100)
                        ->sortable()
                        ->label('Rate'),

                    Tables\Columns\TextColumn::make('difficulty_level')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'beginner' => 'success',
                            'intermediate' => 'warning',
                            'advanced' => 'danger',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('duration_minutes')
                        ->numeric()
                        ->label('Mins'),

                    Tables\Columns\IconColumn::make('is_active')
                        ->boolean()
                        ->label('Active'),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_active'),
                    Tables\Filters\SelectFilter::make('difficulty_level')
                        ->options([
                            'beginner' => 'Beginner',
                            'intermediate' => 'Intermediate',
                            'advanced' => 'Advanced',
                        ]),
                ])
                ->actions([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ])
                ->emptyStateHeading('No lessons found')
                ->emptyStateDescription('Click "New Lesson" to start offering classes.');
        }

        public static function getRelations(): array
        {
            return [];
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListMusicLessons::route('/'),
                'create' => Pages\CreateMusicLesson::route('/create'),
                'edit' => Pages\EditMusicLesson::route('/{record}/edit'),
            ];
        }

        /**
         * Apply Tenant Scoping for the table.
         */
        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ])
                ->where('tenant_id', tenant()->id);
        }
}

<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning;

use Filament\Resources\Resource;

final class LanguageTeacherResource extends Resource
{

    protected static ?string $model = LanguageTeacher::class;
        protected static ?string $navigationIcon = 'heroicon-o-user-group';
        protected static ?string $navigationGroup = 'Language Learning';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Profile Information')
                        ->schema([
                            Forms\Components\Select::make('school_id')
                                ->relationship('school', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\TextInput::make('full_name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('native_language')
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Teaching Experience')
                        ->schema([
                            Forms\Components\KeyValue::make('teaching_languages')
                                ->keyLabel('Language')
                                ->valueLabel('Level (A1-C2)')
                                ->required(),

                            Forms\Components\RichEditor::make('bio')
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('experience_years')
                                ->numeric()
                                ->required(),

                            Forms\Components\TextInput::make('hourly_rate')
                                ->numeric()
                                ->prefix('RUB')
                                ->helperText('In cents')
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Workload & Audit')
                        ->schema([
                            Forms\Components\DateTimePicker::make('created_at')
                                ->disabled()
                                ->label('Joined At'),

                            Forms\Components\TextInput::make('correlation_id')
                                ->default(Str::uuid())
                                ->disabled()
                                ->label('Audit Trace'),

                            Forms\Components\TagsInput::make('tags')
                                ->label('Teacher Specializations'),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('full_name')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('native_language')
                        ->badge(),

                    Tables\Columns\TextColumn::make('experience_years')
                        ->suffix(' years')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('hourly_rate')
                        ->money('RUB', divideBy: 100)
                        ->sortable(),

                    Tables\Columns\TextColumn::make('rating')
                        ->numeric(decimalPlaces: 1)
                        ->icon('heroicon-m-star')
                        ->iconColor('warning'),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('school_id')
                        ->relationship('school', 'name'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListLanguageTeachers::route('/'),
                'create' => Pages\CreateLanguageTeacher::route('/create'),
                'edit' => Pages\EditLanguageTeacher::route('/{record}/edit'),
            ];
        }
}

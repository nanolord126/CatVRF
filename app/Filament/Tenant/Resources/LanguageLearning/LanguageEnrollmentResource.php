<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LanguageLearning;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class LanguageEnrollmentResource extends Resource
{

    protected static ?string $model = LanguageEnrollment::class;
        protected static ?string $navigationIcon = 'heroicon-o-user-plus';
        protected static ?string $navigationGroup = 'Language Learning';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Student & Course')
                        ->schema([
                            Forms\Components\Select::make('student_id')
                                ->relationship('student', 'name')
                                ->required()
                                ->searchable(),

                            Forms\Components\Select::make('course_id')
                                ->relationship('course', 'title')
                                ->required()
                                ->searchable(),

                            Forms\Components\Select::make('teacher_id')
                                ->relationship('teacher', 'full_name')
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Status & Payment')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending Confirmation',
                                    'active' => 'Active Learner',
                                    'completed' => 'Completed Course',
                                    'cancelled' => 'Cancelled/Refunded',
                                ])
                                ->required(),

                            Forms\Components\Select::make('payment_status')
                                ->options([
                                    'unpaid' => 'Unpaid',
                                    'paid' => 'Paid',
                                    'partially_paid' => 'Partially Paid',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('price_paid')
                                ->numeric()
                                ->prefix('RUB')
                                ->helperText('Actual amount paid in cents'),
                        ])->columns(3),

                    Forms\Components\Section::make('Meta & Audit')
                        ->schema([
                            Forms\Components\DateTimePicker::make('expires_at')
                                ->label('Access Expiration'),

                            Forms\Components\TextInput::make('correlation_id')
                                ->default(Str::uuid())
                                ->disabled()
                                ->label('Transaction Trace'),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('student.name')
                        ->searchable()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('course.title')
                        ->searchable()
                        ->limit(20),

                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'completed' => 'info',
                            'cancelled' => 'danger',
                        }),

                    Tables\Columns\TextColumn::make('payment_status')
                        ->badge(),

                    Tables\Columns\TextColumn::make('price_paid')
                        ->money('RUB', divideBy: 100),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status'),
                    Tables\Filters\SelectFilter::make('payment_status'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListLanguageEnrollments::route('/'),
            ];
        }
}

<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\VetGrooming\Infrastructure\Models\TrainingCompletionModel;

class TrainingCompletionResource extends Resource
{
    protected static ?string $model = TrainingCompletionModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Professional Development';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Completion Information')
                    ->schema([
                        Forms\Components\Select::make('master_id')
                            ->relationship('master', 'full_name')
                            ->searchable()
                            ->required()
                            ->label('Master'),
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title')
                            ->searchable()
                            ->required()
                            ->label('Course'),
                        Forms\Components\Select::make('development_plan_id')
                            ->relationship('developmentPlan', 'id')
                            ->label('Development Plan (Optional)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Completion Details')
                    ->schema([
                        Forms\Components\DatePicker::make('completion_date')
                            ->required()
                            ->label('Completion Date'),
                        Forms\Components\TextInput::make('duration_actual_hours')
                            ->numeric()
                            ->label('Actual Duration (Hours)'),
                        Forms\Components\TextInput::make('score')
                            ->numeric()
                            ->suffix('%')
                            ->maxValue(100)
                            ->label('Score'),
                        Forms\Components\Select::make('grade')
                            ->options([
                                'fail' => 'Fail',
                                'pass' => 'Pass',
                                'good' => 'Good',
                                'excellent' => 'Excellent',
                            ])
                            ->label('Grade'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Certificate')
                    ->schema([
                        Forms\Components\TextInput::make('certificate_file')
                            ->label('Certificate File Path'),
                        Forms\Components\DatePicker::make('certificate_expiry_date')
                            ->label('Certificate Expiry Date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Verification')
                    ->schema([
                        Forms\Components\Select::make('verification_status')
                            ->options([
                                'pending' => 'Pending',
                                'verified' => 'Verified',
                                'rejected' => 'Rejected',
                            ])
                            ->required()
                            ->label('Verification Status'),
                        Forms\Components\Select::make('verified_by')
                            ->relationship('verifiedBy', 'name')
                            ->label('Verified By'),
                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Verified At')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('External Certification')
                    ->schema([
                        Forms\Components\TextInput::make('external_certification_id')
                            ->label('External Certification ID'),
                        Forms\Components\TextInput::make('external_platform')
                            ->label('External Platform (e.g., VetEdu, Grooming Academy)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Evidence & Feedback')
                    ->schema([
                        Forms\Components\Textarea::make('feedback')
                            ->rows(3)
                            ->label('Instructor Feedback'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('master.full_name')
                    ->label('Master')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completion_date')
                    ->label('Completed')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->suffix('%')
                    ->numeric(),
                Tables\Columns\TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'excellent' => 'success',
                        'good' => 'primary',
                        'pass' => 'warning',
                        'fail' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('certificate_expiry_date')
                    ->label('Certificate Expiry')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('certificate_expiring')
                    ->query(fn ($query) => $query->whereNotNull('certificate_expiry_date')
                        ->where('certificate_expiry_date', '<=', now()->addDays(30)))
                    ->label('Expiring Within 30 Days'),
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

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Medical\Models\Doctor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * РЕЖИМ ЛЮТЫЙ 2026: DOCTOR RESOURCE
 * 
 * Управление штатом врачей клиники.
 * Обязательная привязка к tenant_id.
 * 
 * @package App\Filament\Tenant\Resources
 */
final class MedicalDoctorResource extends Resource
{
    protected static ?string $model = \App\Domains\Medical\Models\MedicalDoctor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    protected static ?string $navigationGroup = 'Medical Platform';
    protected static ?string $slug = 'medical-doctors';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Professional Profile')
                ->schema([
                    Forms\Components\TextInput::make('full_name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('clinic_id')
                        ->relationship('clinic', 'name')
                        ->required(),

                    Forms\Components\TextInput::make('specialization')
                        ->required(),

                    Forms\Components\TagsInput::make('sub_specializations'),

                    Forms\Components\TextInput::make('experience_years')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->label('Active in Schedule'),
                ])->columns(2),

            Forms\Components\Section::make('Analytics & Tags')
                ->schema([
                    Forms\Components\TextInput::make('rating')
                        ->numeric()
                        ->default(5.0)
                        ->disabled(),

                    Forms\Components\TagsInput::make('tags')
                        ->placeholder('Add tags for AI analysis'),
                ])->columns(2),

            Forms\Components\RichEditor::make('bio')
                ->label('Public Biography')
                ->columnSpanFull(),
        ]);
    }

    /**
     * Таблица врачей клиники.
     * 
     * @param Table $table
     * @return Table
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('specialization')
                    ->label('Spec')
                    ->colors(['primary']),
                Tables\Columns\TextColumn::make('experience_years')->label('Exp')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('rating')->label('⭐')->sortable(),
                Tables\Columns\TextColumn::make('medical_appointments_count')
                    ->label('Visits')
                    ->counts('medicalAppointments'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('specialization'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /**
     * Глобальный скоп по арендатору.
     * 
     * @return Builder
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id);
    }
}

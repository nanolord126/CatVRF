<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\EducationCourseResource\Pages;
use App\Models\Tenants\EducationCourse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EducationCourseResource extends Resource
{
    protected static ?string $model = EducationCourse::class;
    protected static ?string $navigationGroup = 'Marketplace';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\Select::make('type')
                    ->options([
                        'LESSON' => 'Lesson',
                        'WORKSHOP' => 'Workshop',
                        'SPORT_SESSION' => 'Sport Session',
                    ])->required(),
                Forms\Components\TextInput::make('price')->numeric()->prefix('$')->required(),
                Forms\Components\TextInput::make('duration_minutes')->numeric()->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\BadgeColumn::make('type'),
                Tables\Columns\TextColumn::make('price')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')->suffix(' min'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEducationCourses::route('/'),
            'create' => Pages\CreateEducationCourse::route('/create'),
            'edit' => Pages\EditEducationCourse::route('/{record}/edit'),
        ];
    }
}

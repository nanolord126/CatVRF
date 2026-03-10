<?php

namespace App\Filament\Tenant\Resources;

use App\Models\MarketplaceCourse as Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;
    protected static ?string $navigationGroup = 'Education Module';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category')
                    ->options([
                        'Programming' => 'Programming',
                        'Design' => 'Design',
                        'Marketing' => 'Marketing',
                        'Business' => 'Business',
                    ])
                    ->required(),
                Forms\Components\RichEditor::make('description'),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\Repeater::make('modules')
                    ->relationship('modules')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required(),
                        Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                        Forms\Components\Repeater::make('lessons')
                            ->relationship('lessons')
                            ->schema([
                                Forms\Components\TextInput::make('title')->required(),
                                Forms\Components\TextInput::make('video_url')->url(),
                                Forms\Components\RichEditor::make('content'),
                                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                            ])
                    ])
                    ->collapsed()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('price')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('modules_count')->counts('modules'),
            ])
            ->filters([])
            ->actions([
                \App\Filament\Tenant\Resources\Common\VideoCallAction::make('instructor_id'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => CourseResource\Pages\ListCourses::route('/'),
            'create' => CourseResource\Pages\CreateCourse::route('/create'),
            'edit' => CourseResource\Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}

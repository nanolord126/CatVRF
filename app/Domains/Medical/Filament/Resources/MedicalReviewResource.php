<?php declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use App\Domains\Medical\Models\MedicalReview;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

final class MedicalReviewResource extends Resource
{
    protected static ?string $model = MedicalReview::class;

    protected static ?string $navigationGroup = 'Medical';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('doctor_id')->relationship('doctor', 'full_name')->required(),
            Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
            TextInput::make('rating')->numeric()->min(1)->max(5)->required(),
            RichEditor::make('comment')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('doctor.full_name'),
            TextColumn::make('reviewer.name'),
            TextColumn::make('rating')->numeric()->sortable(),
            BadgeColumn::make('status'),
            IconColumn::make('verified_appointment')->boolean(),
            TextColumn::make('helpful_count')->numeric(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}

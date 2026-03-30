<?php declare(strict_types=1);

namespace App\Domains\Medical\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MedicalReviewResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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

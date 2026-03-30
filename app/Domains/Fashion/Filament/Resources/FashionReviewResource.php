<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionReviewResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FashionReview::class;

        protected static ?string $navigationGroup = 'Fashion';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Select::make('product_id')->relationship('product', 'name')->required(),
                Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
                TextInput::make('rating')->required()->numeric()->min(1)->max(5),
                RichEditor::make('comment')->columnSpanFull(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('product.name'),
                TextColumn::make('reviewer.name'),
                TextColumn::make('rating')->numeric()->sortable(),
                BadgeColumn::make('status'),
                IconColumn::make('verified_purchase')->boolean(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}

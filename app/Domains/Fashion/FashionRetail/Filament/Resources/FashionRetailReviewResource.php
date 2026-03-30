<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionRetailReviewResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FashionRetailReview::class;

        protected static ?string $navigationGroup = 'Fashion Retail';

        protected static ?string $navigationLabel = 'Reviews';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Review Details')->schema([
                    Select::make('product_id')->relationship('product', 'name')->required(),
                    Select::make('user_id')->relationship('user', 'name')->required(),
                    TextInput::make('rating')->numeric()->min(1)->max(5)->required(),
                    TextInput::make('title')->required()->maxLength(255),
                    RichEditor::make('comment')->columnSpanFull(),
                    Select::make('status')->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])->required(),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('product.name')->searchable(),
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('rating')->numeric(),
                TextColumn::make('title')->searchable(),
                BadgeColumn::make('status')->colors([
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                ]),
                TextColumn::make('helpful_count')->numeric(),
                TextColumn::make('created_at')->dateTime(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}

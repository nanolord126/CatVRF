<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ReviewResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FlowerReview::class;
        protected static ?string $slug = 'flower-reviews';
        protected static ?string $navigationGroup = 'Flowers';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Forms\Components\Section::make('Review Details')->schema([
                    Forms\Components\Select::make('user_id')
                        ->relationship('user', 'name')
                        ->required(),
                    Forms\Components\Select::make('order_id')
                        ->relationship('order', 'order_number')
                        ->required(),
                    Forms\Components\TextInput::make('rating')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(5),
                    Forms\Components\RichEditor::make('comment')
                        ->required(),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('user.name')->searchable(),
                Tables\Columns\TextColumn::make('rating')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])->actions([
                Tables\Actions\EditAction::make(),
            ]);
        }
}

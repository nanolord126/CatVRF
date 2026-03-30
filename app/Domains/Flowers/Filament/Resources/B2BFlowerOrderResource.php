<?php declare(strict_types=1);

namespace App\Domains\Flowers\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BFlowerOrderResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = B2BFlowerOrder::class;
        protected static ?string $slug = 'b2b-flower-orders';
        protected static ?string $navigationGroup = 'Flowers';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Forms\Components\Select::make('status')->options([
                    'pending' => 'Ожидание',
                    'approved' => 'Одобрено',
                    'rejected' => 'Отклонено',
                    'delivered' => 'Доставлено',
                ])->required(),
                Forms\Components\TextInput::make('total_amount')->numeric(),
                Forms\Components\TextInput::make('commission_amount')->numeric(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('order_number')->searchable(),
                Tables\Columns\TextColumn::make('total_amount'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])->filters([
                Tables\Filters\SelectFilter::make('status'),
            ])->actions([
                Tables\Actions\ViewAction::make(),
            ]);
        }
}

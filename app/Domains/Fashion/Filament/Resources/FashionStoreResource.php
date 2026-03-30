<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionStoreResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = FashionStore::class;

        protected static ?string $navigationGroup = 'Fashion';

        public static function form(Form $form): Form
        {
            return $form->schema([
                TextInput::make('name')->required()->maxLength(255),
                RichEditor::make('description')->columnSpanFull(),
                TextInput::make('logo_url')->url(),
                TextInput::make('cover_image_url')->url(),
                Toggle::make('is_verified')->default(false),
                Toggle::make('is_active')->default(true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('owner.name'),
                TextColumn::make('product_count')->numeric(),
                TextColumn::make('rating')->numeric()->sortable(),
                IconColumn::make('is_verified')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}

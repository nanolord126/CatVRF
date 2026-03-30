<?php declare(strict_types=1);

namespace App\Domains\Logistics\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourierServiceResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = CourierService::class;

        protected static ?string $navigationGroup = 'Logistics';

        public static function form(Form $form): Form
        {
            return $form->schema([
                TextInput::make('company_name')->required()->maxLength(255),
                RichEditor::make('description')->columnSpanFull(),
                TextInput::make('license_number')->required()->unique('courier_services', 'license_number'),
                TextInput::make('service_radius')->required()->numeric(),
                TextInput::make('base_rate')->required()->numeric()->step(0.01),
                TextInput::make('per_km_rate')->required()->numeric()->step(0.01),
                Toggle::make('is_verified')->default(false),
                Toggle::make('is_active')->default(true),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('company_name')->searchable(),
                TextColumn::make('service_radius')->sortable(),
                TextColumn::make('rating')->numeric()->sortable(),
                IconColumn::make('is_verified')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}

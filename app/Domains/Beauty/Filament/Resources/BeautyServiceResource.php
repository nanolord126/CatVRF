<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautyServiceResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = BeautyService::class;

        protected static ?string $slug = 'marketplace/beauty/services';

        protected static ?string $navigationIcon = 'heroicon-o-sparkles';

        protected static ?string $navigationGroup = 'Beauty';

        protected static ?string $navigationLabel = 'Services';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Service Info')->schema([
                    Select::make('salon_id')->relationship('salon', 'name')->required(),
                    Select::make('master_id')->relationship('master', 'full_name')->required(),
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('category')->required(),
                ])->columns(2),

                Section::make('Details & Pricing')->schema([
                    RichEditor::make('description')->columnSpanFull(),
                    TextInput::make('duration_minutes')->required()->numeric()->step(1),
                    TextInput::make('price')->required()->numeric()->step(0.01),
                ])->columns(2),

                Section::make('Status')->schema([
                    Select::make('status')->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])->default('active'),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('salon.name')->searchable(),
                TextColumn::make('master.full_name')->searchable(),
                TextColumn::make('category'),
                TextColumn::make('price')->numeric()->sortable(),
                TextColumn::make('duration_minutes')->numeric(),
                BadgeColumn::make('status')->colors(['active' => 'success', 'inactive' => 'secondary']),
                TextColumn::make('rating')->numeric(),
            ])->filters([])->actions([])->bulkActions([]);
        }

        public static function getPages(): array
        {
            return [
                'index' => (new class extends ListRecords {
                    protected static string $resource = BeautyServiceResource::class;
                })::route('/'),
                'create' => (new class extends CreateRecord {
                    protected static string $resource = BeautyServiceResource::class;
                })::route('/create'),
                'edit' => (new class extends EditRecord {
                    protected static string $resource = BeautyServiceResource::class;
                })::route('/{record}/edit'),
            ];
        }
}

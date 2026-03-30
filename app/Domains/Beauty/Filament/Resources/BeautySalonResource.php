<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeautySalonResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = BeautySalon::class;

        protected static ?string $slug = 'marketplace/beauty/salons';

        protected static ?string $navigationIcon = 'heroicon-o-scissors';

        protected static ?string $navigationGroup = 'Beauty';

        protected static ?string $navigationLabel = 'Salons';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Salon Info')->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('phone')->required(),
                    TextInput::make('email')->required()->email(),
                    TextInput::make('address')->required(),
                    Select::make('owner_id')->relationship('owner', 'name')->required(),
                ])->columns(2),

                Section::make('Details')->schema([
                    RichEditor::make('description')->columnSpanFull(),
                ]),

                Section::make('Status')->schema([
                    Select::make('is_active')->options([
                        true => 'Active',
                        false => 'Inactive',
                    ])->default(true),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('owner.name')->searchable(),
                TextColumn::make('phone'),
                TextColumn::make('email')->searchable(),
                TextColumn::make('address'),
                TextColumn::make('rating')->numeric(),
                BadgeColumn::make('is_active')->colors(['true' => 'success', 'false' => 'secondary']),
                TextColumn::make('created_at')->dateTime(),
            ])->filters([])->actions([])->bulkActions([]);
        }

        public static function getPages(): array
        {
            return [
                'index' => (new class extends ListRecords {
                    protected static string $resource = BeautySalonResource::class;
                })::route('/'),
                'create' => (new class extends CreateRecord {
                    protected static string $resource = BeautySalonResource::class;
                })::route('/create'),
                'edit' => (new class extends EditRecord {
                    protected static string $resource = BeautySalonResource::class;
                })::route('/{record}/edit'),
            ];
        }
}

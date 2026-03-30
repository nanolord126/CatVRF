<?php declare(strict_types=1);

namespace App\Domains\Travel\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TravelAgencyResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = TravelAgency::class;
        protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
        protected static ?string $navigationGroup = 'Travel';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Agency Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required(),
                        TextInput::make('phone')
                            ->tel()
                            ->required(),
                        TextInput::make('address')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('website')
                            ->url()
                            ->nullable(),
                        TextInput::make('license_number')
                            ->unique()
                            ->nullable(),
                        TagsInput::make('specializations'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
                Section::make('Settings')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_verified')
                            ->default(false),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('name')
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('email')
                        ->searchable(),
                    TextColumn::make('phone'),
                    TextColumn::make('rating')
                        ->numeric(2),
                    IconColumn::make('is_verified')
                        ->boolean(),
                    IconColumn::make('is_active')
                        ->boolean(),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    TernaryFilter::make('is_verified'),
                    TernaryFilter::make('is_active'),
                    SelectFilter::make('specializations'),
                ])
                ->actions([
                    ViewAction::make(),
                    EditAction::make(),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ])
                ->defaultSort('created_at', 'desc');
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', tenant()->id);
        }
}

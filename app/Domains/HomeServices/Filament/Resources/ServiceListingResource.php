<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ServiceListingResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = ServiceListing::class;
        protected static ?string $navigationIcon = 'heroicon-o-document-text';
        protected static ?string $navigationLabel = 'Услуги';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация')
                    ->schema([
                        Forms\Components\Select::make('contractor_id')->label('Подрядчик')->relationship('contractor', 'company_name')->required(),
                        Forms\Components\Select::make('category_id')->label('Категория')->relationship('category', 'name')->required(),
                        Forms\Components\TextInput::make('name')->label('Название')->required(),
                        Forms\Components\RichEditor::make('description')->label('Описание')->required(),
                    ]),
                Forms\Components\Section::make('Цена')
                    ->schema([
                        Forms\Components\Select::make('type')->label('Тип')->options([
                            'hourly' => 'Почасовая',
                            'fixed' => 'Фиксированная',
                            'per_unit' => 'За единицу',
                        ])->required(),
                        Forms\Components\TextInput::make('base_price')->label('Цена')->numeric()->required(),
                        Forms\Components\TextInput::make('estimated_duration_minutes')->label('Примерная длительность (мин)')->numeric(),
                    ]),
                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')->label('Активна'),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->label('Услуга')->searchable(),
                    Tables\Columns\TextColumn::make('contractor.company_name')->label('Подрядчик'),
                    Tables\Columns\TextColumn::make('category.name')->label('Категория'),
                    Tables\Columns\TextColumn::make('base_price')->label('Цена')->money('RUB'),
                    Tables\Columns\TextColumn::make('rating')->label('Рейтинг'),
                    Tables\Columns\IconColumn::make('is_active')->label('Активна')->boolean(),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_active')->label('Активные'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages\ListServiceListings::route('/'),
                'create' => \App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages\CreateServiceListing::route('/create'),
                'edit' => \App\Domains\HomeServices\Filament\Resources\ServiceListingResource\Pages\EditServiceListing::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

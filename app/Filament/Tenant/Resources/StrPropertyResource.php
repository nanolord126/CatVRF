<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\ShortTermRentals\Models\StrProperty;
use App\Models\BusinessGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Ресурс управления объектами ShortTermRentals
 */
class StrPropertyResource extends Resource
{
    protected static ?string $model = StrProperty::class;
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'ShortTerm Rentals';
    protected static ?string $label = 'Объект (Дом/ЖК)';
    protected static ?string $pluralLabel = 'Объекты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('business_group_id')
                            ->label('Филиал (ИНН)')
                            ->options(BusinessGroup::all()->pluck('name', 'id'))
                            ->searchable(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'apartment' => 'Квартира',
                                'studio' => 'Студия',
                                'loft' => 'Лофт',
                                'villa' => 'Вилла/Коттедж',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('city')
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Координаты и Статус')
                    ->schema([
                        Forms\Components\TextInput::make('lat')->numeric(),
                        Forms\Components\TextInput::make('lon')->numeric(),
                        Forms\Components\Toggle::make('is_active')->default(true),
                        Forms\Components\Toggle::make('is_verified')->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('Дополнительно')
                    ->schema([
                        Forms\Components\KeyValue::make('tags')->label('Теги аналитики'),
                        Forms\Components\JsonEditor::make('schedule_json')->label('Расписание и Правила'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\IconColumn::make('is_verified')->boolean(),
                Tables\Columns\TextColumn::make('rating')->numeric(1)->sortable(),
                Tables\Columns\TextColumn::make('review_count')->label('Отзывы'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                // Здесь можно добавить фильтры по tenant через scope
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => StrPropertyResource\Pages\ListStrProperties::route('/'),
            'create' => StrPropertyResource\Pages\CreateStrProperty::route('/create'),
            'edit' => StrPropertyResource\Pages\EditStrProperty::route('/{record}/edit'),
        ];
    }
}

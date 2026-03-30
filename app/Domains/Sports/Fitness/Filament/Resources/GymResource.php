<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GymResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Gym::class;
        protected static ?string $navigationIcon = 'heroicon-o-home';
        protected static ?string $navigationLabel = 'Клубы';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Информация')->schema([
                    Forms\Components\TextInput::make('name')->label('Название')->required(),
                    Forms\Components\RichEditor::make('description')->label('Описание'),
                    Forms\Components\TextInput::make('address')->label('Адрес')->required(),
                ]),
                Forms\Components\Section::make('Цены')->schema([
                    Forms\Components\TextInput::make('monthly_membership_price')->label('Месячное членство')->numeric(),
                    Forms\Components\TextInput::make('annual_membership_price')->label('Годовое членство')->numeric(),
                ]),
                Forms\Components\Section::make('Статус')->schema([
                    Forms\Components\Toggle::make('is_verified')->label('Проверен'),
                    Forms\Components\Toggle::make('is_active')->label('Активен'),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->label('Клуб')->searchable(),
                    Tables\Columns\TextColumn::make('address')->label('Адрес')->searchable(),
                    Tables\Columns\TextColumn::make('rating')->label('Рейтинг')->sortable(),
                    Tables\Columns\TextColumn::make('member_count')->label('Членов'),
                    Tables\Columns\IconColumn::make('is_verified')->label('Проверен')->boolean(),
                    Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_verified')->label('Проверённые'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\Sports\Fitness\Filament\Resources\GymResource\Pages\ListGyms::route('/'),
                'create' => \App\Domains\Sports\Fitness\Filament\Resources\GymResource\Pages\CreateGym::route('/create'),
                'edit' => \App\Domains\Sports\Fitness\Filament\Resources\GymResource\Pages\EditGym::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
        }
}

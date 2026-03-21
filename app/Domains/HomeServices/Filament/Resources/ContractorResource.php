<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Filament\Resources;

use App\Domains\HomeServices\Models\Contractor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ContractorResource extends Resource
{
    protected static ?string $model = Contractor::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Подрядчики';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Информация')
                ->schema([
                    Forms\Components\TextInput::make('company_name')->label('Компания')->required(),
                    Forms\Components\RichEditor::make('description')->label('Описание'),
                    Forms\Components\TextInput::make('phone')->label('Телефон'),
                    Forms\Components\TextInput::make('website')->label('Сайт')->url(),
                ]),
            Forms\Components\Section::make('Параметры')
                ->schema([
                    Forms\Components\TextInput::make('hourly_rate')->label('Ставка/час')->numeric(),
                    Forms\Components\Toggle::make('is_verified')->label('Проверен'),
                    Forms\Components\Toggle::make('is_active')->label('Активен'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->label('Компания')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email'),
                Tables\Columns\TextColumn::make('rating')->label('Рейтинг')->sortable(),
                Tables\Columns\TextColumn::make('review_count')->label('Отзывов'),
                Tables\Columns\TextColumn::make('job_count')->label('Работ'),
                Tables\Columns\IconColumn::make('is_verified')->label('Проверен')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')->label('Проверённые'),
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
            'index' => \App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages\ListContractors::route('/'),
            'create' => \App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages\CreateContractor::route('/create'),
            'edit' => \App\Domains\HomeServices\Filament\Resources\ContractorResource\Pages\EditContractor::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }
}

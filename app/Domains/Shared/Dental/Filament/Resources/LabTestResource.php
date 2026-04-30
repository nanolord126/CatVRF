<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Dental\Infrastructure\Models\LabTestModel;

final class LabTestResource extends Resource
{
    protected static ?string $model = LabTestModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Лабораторные тесты';
    protected static ?string $navigationGroup = 'Стоматология';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'name')
                            ->searchable()
                            ->required()
                            ->label('Пациент'),
                        Forms\Components\Select::make('doctor_id')
                            ->relationship('doctor', 'name')
                            ->searchable()
                            ->required()
                            ->label('Врач'),
                        Forms\Components\Select::make('lab_test_type_id')
                            ->relationship('labTestType', 'name')
                            ->searchable()
                            ->required()
                            ->label('Тип анализа'),
                        Forms\Components\TextInput::make('barcode')
                            ->disabled()
                            ->label('Штрих-код'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Статус и даты')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'ordered' => 'Заказан',
                                'sample_collected' => 'Образец взят',
                                'in_progress' => 'В обработке',
                                'completed' => 'Готов',
                                'cancelled' => 'Отменён',
                            ])
                            ->default('ordered')
                            ->required()
                            ->label('Статус'),
                        Forms\Components\DateTimePicker::make('sample_collected_at')
                            ->label('Время взятия образца'),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Время завершения'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Результаты')
                    ->schema([
                        Forms\Components\Repeater::make('results')
                            ->relationship('results')
                            ->schema([
                                Forms\Components\TextInput::make('parameter_name')
                                    ->required()
                                    ->label('Параметр'),
                                Forms\Components\TextInput::make('parameter_value')
                                    ->required()
                                    ->label('Значение'),
                                Forms\Components\TextInput::make('unit')
                                    ->label('Единица'),
                                Forms\Components\TextInput::make('reference_range')
                                    ->label('Референсные значения'),
                                Forms\Components\Toggle::make('is_abnormal')
                                    ->label('Отклонение от нормы'),
                            ])
                            ->columns(5)
                            ->label('Результаты анализа'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Штрих-код')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Пациент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Врач')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('labTestType.name')
                    ->label('Тип анализа')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'ordered',
                        'warning' => 'sample_collected',
                        'info' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('cost')
                    ->label('Стоимость')
                    ->money('rub'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ordered' => 'Заказан',
                        'sample_collected' => 'Образец взят',
                        'in_progress' => 'В обработке',
                        'completed' => 'Готов',
                        'cancelled' => 'Отменён',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Dental\Filament\Resources\LabTestResource\Pages\ListLabTests::route('/'),
            'create' => \Modules\Dental\Filament\Resources\LabTestResource\Pages\CreateLabTest::route('/create'),
            'view' => \Modules\Dental\Filament\Resources\LabTestResource\Pages\ViewLabTest::route('/{record}'),
            'edit' => \Modules\Dental\Filament\Resources\LabTestResource\Pages\EditLabTest::route('/{record}/edit'),
        ];
    }
}

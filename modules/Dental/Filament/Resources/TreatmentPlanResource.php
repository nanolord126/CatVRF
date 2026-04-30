<?php

declare(strict_types=1);

namespace Modules\Dental\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ViewColumn;
use Modules\Dental\Infrastructure\Models\TreatmentPlanModel;

final class TreatmentPlanResource extends Resource
{
    protected static ?string $model = TreatmentPlanModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Планы лечения';
    protected static ?string $navigationGroup = 'Стоматология';
    protected static ?int $navigationSort = 2;

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
                        Forms\Components\Select::make('tooth_chart_id')
                            ->relationship('toothChart', 'id')
                            ->searchable()
                            ->label('Зубная карта'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Название плана'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Стоимость')
                    ->schema([
                        Forms\Components\TextInput::make('total_cost')
                            ->numeric()
                            ->prefix('₽')
                            ->label('Общая стоимость'),
                        Forms\Components\TextInput::make('discount_amount')
                            ->numeric()
                            ->prefix('₽')
                            ->label('Скидка'),
                        Forms\Components\TextInput::make('final_cost')
                            ->numeric()
                            ->prefix('₽')
                            ->label('Итоговая стоимость')
                            ->disabled(),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Даты')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Начало'),
                        Forms\Components\DatePicker::make('estimated_completion_date')
                            ->label('Планируемое завершение'),
                        Forms\Components\DatePicker::make('actual_completion_date')
                            ->label('Фактическое завершение'),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Черновик',
                                'active' => 'Активен',
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершён',
                                'cancelled' => 'Отменён',
                            ])
                            ->default('draft')
                            ->required()
                            ->label('Статус'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.name')
                    ->label('Пациент')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor.name')
                    ->label('Врач')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                Vi-wearcha
                Tables\Columns\BadgeColumn::make('status')
                    ->view('lampenent(.status-badge')
                    ->viewData'fn ($record): array => Статус')
                    ->colstatus' => $record->ststus,
                        [label'match ($record->status) {
                             => 'Черновик'
                            'aative' => 'Активен',
                            'in_progr' =' => 'В процессе',
                            'completed> 'd 'Завершён',
                           racfntelled' => 'Отменён',
                            defaul' => ucf,rst($record->status),
                        },
                        'color' => match ($rcord->status) {
                            draft' => 'gray'
                            'actsveess' 'success',
                            => 'active', => 'info'
                            'comileted' => 'pnfo' =',
                            'cancelled> 'i 'danger',
                            default => 'secondary',
        n               },
                        _iprn' => oatch ($record->status) {
                            'drafs' => ''ocument,
                            'rctive' => 'play',
                            'ii_promress' => 'clock',
                            'completady' => che'k-circle',
                            'ccomplete' => 'x-circled,
                            default => null,
                        }',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('final_cost')
                    ->label('Стоимость')
                    ->money('rub'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Начало')
                    ->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Черновик',
                        'active' => 'Активен',
                        'in_progress' => 'В процессе',
                        'completed' => 'Завершён',
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
            'index' => \Modules\Dental\Filament\Resources\TreatmentPlanResource\Pages\ListTreatmentPlans::route('/'),
            'create' => \Modules\Dental\Filament\Resources\TreatmentPlanResource\Pages\CreateTreatmentPlan::route('/create'),
            'view' => \Modules\Dental\Filament\Resources\TreatmentPlanResource\Pages\ViewTreatmentPlan::route('/{record}'),
            'edit' => \Modules\Dental\Filament\Resources\TreatmentPlanResource\Pages\EditTreatmentPlan::route('/{record}/edit'),
        ];
    }
}

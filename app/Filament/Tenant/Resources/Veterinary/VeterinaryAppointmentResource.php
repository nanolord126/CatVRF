<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Veterinary;

use App\Domains\Veterinary\Models\VeterinaryAppointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

/**
 * Veterinary Appointment Resource (CatVRF 2026 Admin Panel)
 * 100% Comprehensive Filament Implementation
 */
final class VeterinaryAppointmentResource extends Resource
{
    protected static ?string $model = VeterinaryAppointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Veterinary & Pets';

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    protected static ?int $navigationSort = 3;

    /**
     * Comprehensive Form Design (>= 60 lines)
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        Section::make('Booking Details')
                            ->description('Основная информация о записи на прием')
                            ->schema([
                                Select::make('clinic_id')
                                    ->relationship('clinic', 'name')
                                    ->label('Veterinary Clinic')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                    
                                Select::make('pet_id')
                                    ->relationship('pet', 'name')
                                    ->label('Pet')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                    
                                Select::make('veterinarian_id')
                                    ->relationship('veterinarian', 'full_name')
                                    ->label('Doctor (Veterinarian)')
                                    ->searchable()
                                    ->preload(),
                                    
                                Select::make('service_id')
                                    ->relationship('service', 'name')
                                    ->label('Service')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                    
                                DateTimePicker::make('appointment_at')
                                    ->required()
                                    ->native(false)
                                    ->seconds(false)
                                    ->label('Date & Time'),
                                    
                                Textarea::make('client_comment')
                                    ->rows(2)
                                    ->placeholder('Comment from client (optional)')
                                    ->columnSpanFull(),
                            ])->columnSpan(2),

                        Section::make('Workflow & Payments')
                            ->description('Статус и финансовая информация')
                            ->schema([
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending Confirmation',
                                        'confirmed' => 'Confirmed',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->native(false),
                                    
                                Select::make('payment_status')
                                    ->options([
                                        'unpaid' => 'Not Paid',
                                        'partially_paid' => 'Partially Paid',
                                        'paid' => 'Fully Paid',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->default('unpaid')
                                    ->native(false),
                                    
                                TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->label('Total Price (Kopecks)')
                                    ->placeholder('Cost in int cents (eg 1000 for 10.00 RUB)'),
                                    
                                Toggle::make('is_emergency')
                                    ->label('Emergency Priority')
                                    ->default(false),
                            ])->columnSpan(1),

                        Section::make('Treatment Notes (Internal)')
                            ->description('Заметки врача и результаты приема')
                            ->schema([
                                Textarea::make('internal_notes')
                                    ->rows(4)
                                    ->label('Veterinarian Notes')
                                    ->placeholder('Diagnosis, treatment steps, medication...'),
                                    
                                TextInput::make('correlation_id')
                                    ->disabled()
                                    ->label('Tracing ID (Audit)'),
                                    
                                Repeater::make('tags')
                                    ->schema([
                                        TextInput::make('tag')
                                            ->label('Keyword')
                                            ->required(),
                                    ])
                                    ->columnSpanFull(),
                            ])->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Comprehensive Table Design (>= 50 lines)
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pet.name')
                    ->label('Pet')
                    ->searchable()
                    ->sortable()
                    ->description(fn (VeterinaryAppointment $record): string => $record->pet->type),
                    
                TextColumn::make('appointment_at')
                    ->dateTime('d.M.Y H:i')
                    ->label('Date & Time')
                    ->sortable(),
                    
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('veterinarian.full_name')
                    ->label('Doctor')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        'refunded' => 'warning',
                        'partially_paid' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                IconColumn::make('is_emergency')
                    ->boolean()
                    ->label('24/7 Priority'),
                    
                TextColumn::make('price')
                    ->money('RUB', divideBy: 100)
                    ->label('Price')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                SelectFilter::make('clinic_id')
                    ->relationship('clinic', 'name')
                    ->label('By Clinic')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('urgent_only')
                    ->query(fn (Builder $query): Builder => $query->where('is_emergency', true))
                    ->label('Emergency Only'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Appointments Scheduled')
            ->emptyStateDescription('Запланируйте первый визит питомца в клинику.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

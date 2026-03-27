<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\WeddingPlanning;

use App\Domains\WeddingPlanning\Models\WeddingEvent;
use App\Domains\WeddingPlanning\Models\WeddingBooking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Domains\WeddingPlanning\Services\WeddingService;
use App\Domains\WeddingPlanning\Services\AIWeddingPlannerConstructor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * WeddingEventResource
 *
 * Layer 4: Admin UI (Filament)
 * Предоставляет полный интерфейс управления свадьбой.
 *
 * @version 1.0.0
 * @author CatVRF
 */
class WeddingEventResource extends Resource
{
    protected static ?string $model = WeddingEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?string $navigationGroup = 'Wedding Planning';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Wedding Planning')
                ->tabs([
                    // 1. Основная информация (General Info)
                    Forms\Components\Tabs\Tab::make('General Information')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\TextInput::make('title')
                                        ->required()
                                        ->maxLength(255)
                                        ->placeholder('Wedding of Ivan & Maria'),
                                    Forms\Components\DateTimePicker::make('event_date')
                                        ->required()
                                        ->label('Event Date & Time'),
                                    Forms\Components\Select::make('status')
                                        ->options([
                                            'planning' => 'Planning',
                                            'confirmed' => 'Confirmed',
                                            'active' => 'Active',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled',
                                        ])
                                        ->required()
                                        ->default('planning'),
                                    Forms\Components\TextInput::make('location')
                                        ->maxLength(255)
                                        ->placeholder('Palace of Weddings / Venue Name'),
                                    Forms\Components\TextInput::make('guest_count')
                                        ->numeric()
                                        ->default(0)
                                        ->suffix('guests'),
                                    Forms\Components\TextInput::make('total_budget')
                                        ->numeric()
                                        ->label('Total Budget')
                                        ->suffix('Kopecks (RUB)')
                                        ->helperText('Budget in kopecks (e.g. 1 000 000 for 10 000 RUB)'),
                                    Forms\Components\FileUpload::make('cover_image')
                                        ->image()
                                        ->directory('weddings/covers')
                                        ->columnSpan(2),
                                    Forms\Components\TagsInput::make('tags')
                                        ->label('Labels (Boho, Luxury, Winter, etc)'),
                                ]),
                        ]),

                    // 2. Бронирования (Bookings & Vendors)
                    Forms\Components\Tabs\Tab::make('Bookings')
                        ->icon('heroicon-o-calendar-days')
                        ->schema([
                            Forms\Components\Repeater::make('bookings')
                                ->relationship('bookings')
                                ->schema([
                                    Forms\Components\Grid::make(4)
                                        ->schema([
                                            Forms\Components\Select::make('bookable_id')
                                                ->label('Provider / Vendor')
                                                ->searchable()
                                                ->required(), // Morph placeholder
                                            Forms\Components\TextInput::make('amount')
                                                ->numeric()
                                                ->label('Total Amount'),
                                            Forms\Components\TextInput::make('prepayment_amount')
                                                ->numeric()
                                                ->label('Prepayment'),
                                            Forms\Components\Select::make('status')
                                                ->options([
                                                    'pending' => 'Pending',
                                                    'reserved' => 'Reserved',
                                                    'paid_full' => 'Paid Full',
                                                    'cancelled' => 'Cancelled',
                                                ]),
                                            Forms\Components\Textarea::make('notes')
                                                ->columnSpanFull()
                                                ->rows(1),
                                        ]),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['bookable_type'] ?? 'Select Provider'),
                        ]),

                    // 3. AI Конструктор (Integrate AI Assistant)
                    Forms\Components\Tabs\Tab::make('AI Assistant')
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Forms\Components\Section::make('AI Constructor Generation')
                                ->description('Use AI to generate wedding timeline and budget distribution.')
                                ->schema([
                                    Forms\Components\Placeholder::make('ai_info')
                                        ->content('Click "Generate Plan" below to trigger AI Constructor based on current budget and style.'),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\Select::make('ai_style')
                                                ->options([
                                                    'boho' => 'Boho',
                                                    'luxury' => 'Luxury',
                                                    'classic' => 'Classic',
                                                    'rustic' => 'Rustic',
                                                ])
                                                ->label('Preferable AI Style'),
                                            Forms\Components\Actions::make([
                                                Forms\Components\Actions\Action::make('generate_plan')
                                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                                        $constructor = new AIWeddingPlannerConstructor();
                                                        $plan = $constructor->generateWeddingPlan(
                                                            (int) $get('total_budget'),
                                                            (string) $get('ai_style'),
                                                            (int) $get('guest_count')
                                                        );
                                                        // UI Logic: Fill in hidden data or notify
                                                        Notification::make()->title('AI Plan Generated!')->success()->send();
                                                    })
                                                    ->color('success')
                                                    ->icon('heroicon-o-light-bulb'),
                                            ])->columnSpan(2),
                                        ]),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('event_date')
                ->dateTime()
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'planning' => 'gray',
                    'confirmed' => 'info',
                    'active' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                }),
            Tables\Columns\TextColumn::make('guest_count')
                ->label('Guests')
                ->numeric(),
            Tables\Columns\TextColumn::make('total_budget')
                ->label('Budget (RUB)')
                ->money('RUB')
                ->formatStateUsing(fn ($state) => (float)$state / 100),
            Tables\Columns\TextColumn::make('correlation_id')
                ->label('Trace ID')
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'planning' => 'Planning',
                    'confirmed' => 'Confirmed',
                    'active' => 'Active',
                    'completed' => 'Completed',
                ]),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('wedding_events.tenant_id', filament()->getTenant()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecords::route('/'), // Placeholder ListRecord page should exist
        ];
    }
}

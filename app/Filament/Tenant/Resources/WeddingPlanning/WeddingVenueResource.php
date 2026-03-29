<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\WeddingPlanning;

use App\Domains\WeddingPlanning\Models\WeddingVenue;
use Filament\Forms\{Form, Components\Section, Components\TextInput, Components\Select, Components\Toggle, Components\Hidden, Components\RichEditor, Components\FileUpload, Components\Textarea};
use Filament\Resources\Resource;
use Filament\Tables\{Table, Columns\TextColumn, Columns\BadgeColumn, Columns\BooleanColumn, Filters\SelectFilter, Filters\TernaryFilter, Filters\TrashedFilter};
use Filament\Tables\Actions\{EditAction, ViewAction, DeleteAction, RestoreAction, BulkActionGroup, DeleteBulkAction, BulkAction, ActionGroup};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

final class WeddingVenueResource extends Resource
{
    protected static ?string $model = WeddingVenue::class;
    protected static ?string $navigationIcon = 'heroicon-m-heart';
    protected static ?string $navigationGroup = 'Events & Celebrations';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('💐 Информация о площадке')
                ->icon('heroicon-m-heart')
                ->schema([
                    TextInput::make('uuid')->label('UUID')->default(fn () => Str::uuid())->disabled()->dehydrated()->columnSpan(2),
                    TextInput::make('name')->label('Название')->required()->columnSpan(2),
                    TextInput::make('address')->label('Адрес')->required()->columnSpan(2),
                    TextInput::make('city')->label('Город')->required()->columnSpan(1),
                    TextInput::make('phone')->label('Телефон')->required()->columnSpan(1),
                    TextInput::make('capacity_min')->label('Минимум гостей')->numeric()->columnSpan(1),
                    TextInput::make('capacity_max')->label('Максимум гостей')->numeric()->columnSpan(1),
                    TextInput::make('price_per_guest')->label('Цена за гостя (₽)')->numeric()->required()->columnSpan(1),
                    RichEditor::make('description')->label('Описание')->columnSpan('full'),
                    FileUpload::make('gallery')->label('Фотогалерея')->image()->directory('wedding')->multiple()->columnSpan(1),
                ])->columns(4),

            Section::make('Услуги')
                ->icon('heroicon-m-check-circle')
                ->schema([
                    Toggle::make('has_catering')->label('🍽️ Кейтеринг')->columnSpan(1),
                    Toggle::make('has_dj')->label('🎵 DJ')->columnSpan(1),
                    Toggle::make('has_photographer')->label('📸 Фотограф')->columnSpan(1),
                    Toggle::make('has_decoration')->label('🌹 Декор')->columnSpan(1),
                ])->columns(4),

            Section::make('Рейтинг')
                ->icon('heroicon-m-star')
                ->schema([
                    TextColumn::make('rating')->label('Рейтинг')->numeric()->disabled()->columnSpan(1),
                    Toggle::make('is_active')->label('Активный')->default(true)->columnSpan(1),
                    Toggle::make('is_featured')->label('⭐ Популярная')->columnSpan(1),
                ])->columns(3),

            Section::make('Служебная информация')
                ->icon('heroicon-m-cog-6-tooth')
                ->schema([
                    Hidden::make('tenant_id')->default(fn () => tenant('id')),
                    Hidden::make('correlation_id')->default(fn () => Str::uuid()),
                ])->columns('full'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Площадка')->searchable()->sortable()->limit(40),
            TextColumn::make('city')->label('Город')->searchable(),
            TextColumn::make('capacity_max')->label('Гостей')->numeric()->alignment('center')->badge()->color('info'),
            TextColumn::make('price_per_guest')->label('За гостя (₽)')->money('RUB', divideBy: 100)->sortable(),
            TextColumn::make('rating')->label('★')->formatStateUsing(fn ($state) => '★ ' . number_format($state, 1))->badge()->color(fn ($state) => $state >= 4.5 ? 'success' : 'warning'),
            BooleanColumn::make('has_catering')->label('🍽️')->toggleable(),
            BooleanColumn::make('has_photographer')->label('📸')->toggleable(),
            BooleanColumn::make('is_featured')->label('⭐')->toggleable(),
            BooleanColumn::make('is_active')->label('Активный')->toggleable()->sortable(),
        ])->filters([
            SelectFilter::make('city')->label('Город')->options(['moscow' => 'Москва', 'spb' => 'СПб', 'kazan' => 'Казань'])->multiple(),
            TernaryFilter::make('has_catering')->label('С кейтерингом'),
            TrashedFilter::make(),
        ])->actions([
            ActionGroup::make([ViewAction::make(), EditAction::make(), DeleteAction::make(), RestoreAction::make()]),
        ])->bulkActions([
            BulkActionGroup::make([DeleteBulkAction::make(), BulkAction::make('activate')->label('Активировать')->icon('heroicon-m-check-circle')->color('success')->action(function ($records) { $records->each(fn ($r) => $r->update(['is_active' => true])); })->deselectRecordsAfterCompletion()]),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\WeddingPlanning\Pages\ListVenues::route('/'),
            'create' => \App\Filament\Tenant\Resources\WeddingPlanning\Pages\CreateVenue::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\WeddingPlanning\Pages\EditVenue::route('/{record}/edit'),
        ];
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }
}

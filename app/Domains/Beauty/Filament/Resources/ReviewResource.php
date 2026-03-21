<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources;

use App\Domains\Beauty\Models\Review;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $slug = 'marketplace/beauty/reviews';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $navigationLabel = 'Reviews';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Review Details')->schema([
                Select::make('service_id')->relationship('service', 'name')->required(),
                Select::make('user_id')->relationship('user', 'name')->required(),
                TextInput::make('rating')->numeric()->min(1)->max(5)->required(),
                TextInput::make('title')->required()->maxLength(255),
                RichEditor::make('comment')->columnSpanFull(),
            ]),

            Section::make('Status')->schema([
                Select::make('status')->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ])->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('service.name')->searchable(),
            TextColumn::make('user.name')->searchable(),
            TextColumn::make('rating')->numeric(),
            TextColumn::make('title')->searchable(),
            BadgeColumn::make('status')->colors([
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
            ]),
            TextColumn::make('created_at')->dateTime(),
        ])->filters([])->actions([])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => (new class extends ListRecords {
                protected static string $resource = ReviewResource::class;
            })::route('/'),
            'create' => (new class extends CreateRecord {
                protected static string $resource = ReviewResource::class;
            })::route('/create'),
            'edit' => (new class extends EditRecord {
                protected static string $resource = ReviewResource::class;
            })::route('/{record}/edit'),
        ];
    }
}

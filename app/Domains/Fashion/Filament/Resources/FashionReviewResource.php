<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionReview;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

final class FashionReviewResource extends Resource
{
    protected static ?string $model = FashionReview::class;

    protected static ?string $navigationGroup = 'Fashion';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('product_id')->relationship('product', 'name')->required(),
            Select::make('reviewer_id')->relationship('reviewer', 'name')->required(),
            TextInput::make('rating')->required()->numeric()->min(1)->max(5),
            RichEditor::make('comment')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('product.name'),
            TextColumn::make('reviewer.name'),
            TextColumn::make('rating')->numeric()->sortable(),
            BadgeColumn::make('status'),
            IconColumn::make('verified_purchase')->boolean(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}

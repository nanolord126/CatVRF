<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionEmailCampaign;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class FashionEmailCampaignResource extends Resource
{
    protected static ?string $model = FashionEmailCampaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Fashion Advanced';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('subject'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('sent_count'),
                Tables\Columns\TextColumn::make('opened_count'),
                Tables\Columns\TextColumn::make('clicked_count'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('subject')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'scheduled' => 'warning',
                        'sent' => 'success',
                        'active' => 'info',
                    }),
                Tables\Columns\TextColumn::make('sent_count')->numeric(),
                Tables\Columns\TextColumn::make('opened_count')->numeric(),
                Tables\Columns\TextColumn::make('clicked_count')->numeric(),
                Tables\Columns\TextColumn::make('converted_count')->numeric(),
                Tables\Columns\TextColumn::make('scheduled_for')->dateTime(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'scheduled' => 'Scheduled', 'sent' => 'Sent', 'active' => 'Active']),
            ]);
    }
}

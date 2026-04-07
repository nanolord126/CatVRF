<?php declare(strict_types=1);

namespace App\Domains\Photography\Filament\Resources;

use Filament\Resources\Resource;

final class PhotoReviewResource extends Resource
{

    protected static ?string $model = PhotoReview::class;

    	protected static ?string $navigationIcon = 'heroicon-o-star';

    	protected static ?string $navigationGroup = 'Photography';

    	public static function form(Form $form): Form
    	{
    		return $form
    			->schema([
    				Forms\Components\Select::make('photo_studio_id')
    					->relationship('studio', 'name')
    					->required(),
    				Forms\Components\Select::make('photographer_id')
    					->relationship('photographer', 'full_name')
    					->required(),
    				Forms\Components\TextInput::make('rating')
    					->numeric()
    					->min(1)
    					->max(5)
    					->required(),
    				Forms\Components\Textarea::make('comment')
    					->maxLength(1000),
    				Forms\Components\Toggle::make('is_verified_purchase')
    					->disabled(),
    			]);
    	}

    	public static function table(Table $table): Table
    	{
    		return $table
    			->columns([
    				Tables\Columns\TextColumn::make('studio.name'),
    				Tables\Columns\TextColumn::make('photographer.full_name'),
    				Tables\Columns\TextColumn::make('rating'),
    				Tables\Columns\TextColumn::make('helpful_count'),
    				Tables\Columns\IconColumn::make('is_verified_purchase'),
    				Tables\Columns\TextColumn::make('created_at')
    					->dateTime(),
    			])
    			->filters([
    				Tables\Filters\SelectFilter::make('rating')
    					->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
    			])
    			->actions([
    				Tables\Actions\ViewAction::make(),
    				Tables\Actions\DeleteAction::make(),
    			]);
    	}
}

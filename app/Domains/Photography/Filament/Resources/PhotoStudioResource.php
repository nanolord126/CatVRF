<?php declare(strict_types=1);

namespace App\Domains\Photography\Filament\Resources;

use Filament\Resources\Resource;

final class PhotoStudioResource extends Resource
{

    protected static ?string $model = PhotoStudio::class;

    	protected static ?string $navigationIcon = 'heroicon-o-camera';

    	protected static ?string $navigationGroup = 'Photography';

    	public static function form(Form $form): Form
    	{
    		return $form
    			->schema([
    				Forms\Components\TextInput::make('name')
    					->required()
    					->maxLength(255),
    				Forms\Components\TextInput::make('address')
    					->required(),
    				Forms\Components\TextInput::make('phone')
    					->tel()
    					->required(),
    				Forms\Components\TextInput::make('email')
    					->email(),
    				Forms\Components\Textarea::make('description')
    					->maxLength(1000),
    				Forms\Components\TagsInput::make('studio_types'),
    				Forms\Components\Toggle::make('is_verified'),
    				Forms\Components\Toggle::make('is_active')
    					->default(true),
    			]);
    	}

    	public static function table(Table $table): Table
    	{
    		return $table
    			->columns([
    				Tables\Columns\TextColumn::make('name')
    					->searchable(),
    				Tables\Columns\TextColumn::make('address'),
    				Tables\Columns\TextColumn::make('phone'),
    				Tables\Columns\TextColumn::make('rating')
    					->numeric(2),
    				Tables\Columns\IconColumn::make('is_verified'),
    				Tables\Columns\IconColumn::make('is_active'),
    			])
    			->filters([
    				Tables\Filters\SelectFilter::make('is_verified'),
    				Tables\Filters\SelectFilter::make('is_active'),
    			])
    			->actions([
    				Tables\Actions\ViewAction::make(),
    				Tables\Actions\EditAction::make(),
    			])
    			->bulkActions([
    				Tables\Actions\BulkActionGroup::make([
    					Tables\Actions\DeleteBulkAction::make(),
    				]),
    			]);
    	}
}

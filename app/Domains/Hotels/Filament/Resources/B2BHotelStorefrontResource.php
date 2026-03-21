<?php

declare(strict_types=1);

namespace App\Domains\Hotels\Filament\Resources;

use App\Domains\Hotels\Models\B2BHotelStorefront;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class B2BHotelStorefrontResource extends Resource
{
	protected static ?string $model = B2BHotelStorefront::class;
	protected static ?string $navigationIcon = 'heroicon-o-building-2';
	protected static ?string $navigationGroup = 'Hotels B2B';

	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\TextInput::make('company_name')->required(),
			Forms\Components\TextInput::make('inn')->required()->unique(),
			Forms\Components\Textarea::make('description'),
			Forms\Components\TextInput::make('wholesale_discount')->numeric(),
			Forms\Components\TextInput::make('min_booking_nights')->numeric()->default(3),
			Forms\Components\Toggle::make('is_verified')->disabled(),
			Forms\Components\Toggle::make('is_active')->default(true),
		]);
	}

	public static function table(Table $table): Table
	{
		return $table->columns([
			Tables\Columns\TextColumn::make('company_name')->searchable(),
			Tables\Columns\TextColumn::make('inn'),
			Tables\Columns\TextColumn::make('wholesale_discount'),
			Tables\Columns\IconColumn::make('is_verified'),
			Tables\Columns\IconColumn::make('is_active'),
		])->filters([Tables\Filters\SelectFilter::make('is_verified')])->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()]);
	}
}

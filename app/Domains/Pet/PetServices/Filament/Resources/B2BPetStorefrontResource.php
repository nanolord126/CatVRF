<?php

declare(strict_types=1);
 namespace App\Domains\Pet\PetServices\Filament\Resources; use App\Domains\Pet\PetServices\Models\B2BPetStorefront; use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table; /**
 * B2BPetStorefrontResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BPetStorefrontResource extends Resource { protected static ?string $model=B2BPetStorefront::class; protected static ?string $navigationIcon='heroicon-o-sparkles'; protected static ?string $navigationGroup='Pet Services B2B'; public static function form(Form $form): Form { return $form->schema([Forms\Components\TextInput::make('company_name')->required(),Forms\Components\TextInput::make('inn')->required()->unique(),Forms\Components\Textarea::make('description'),Forms\Components\TextInput::make('wholesale_discount')->numeric(),Forms\Components\TextInput::make('min_order_amount')->numeric()->default(50000),Forms\Components\Toggle::make('is_verified')->disabled(),Forms\Components\Toggle::make('is_active')->default(true),]); } public static function table(Table $table): Table { return $table->columns([Tables\Columns\TextColumn::make('company_name')->searchable(),Tables\Columns\TextColumn::make('inn'),Tables\Columns\TextColumn::make('wholesale_discount'),Tables\Columns\IconColumn::make('is_verified'),Tables\Columns\IconColumn::make('is_active'),])->filters([Tables\Filters\SelectFilter::make('is_verified')])->actions([Tables\Actions\ViewAction::make(),Tables\Actions\EditAction::make()]); } }

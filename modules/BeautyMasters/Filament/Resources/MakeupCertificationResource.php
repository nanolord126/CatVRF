<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources;

use Modules\BeautyMasters\Infrastructure\Models\MakeupCertificationModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MakeupCertificationResource extends Resource
{
    protected static ?string $model = MakeupCertificationModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Beauty Masters';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('master_id')
                    ->relationship('master', 'first_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Master'),

                Forms\Components\Select::make('certification_type')
                    ->options([
                        'internal' => 'Internal CatCRM Certification',
                        'external' => 'External Certification',
                    ])
                    ->required()
                    ->label('Certification Type'),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Certification Name'),

                Forms\Components\TextInput::make('issuer')
                    ->maxLength(255)
                    ->label('Issuer'),

                Forms\Components\DatePicker::make('issue_date')
                    ->required()
                    ->label('Issue Date'),

                Forms\Components\DatePicker::make('expiry_date')
                    ->label('Expiry Date'),

                Forms\Components\TextInput::make('certificate_number')
                    ->maxLength(255)
                    ->label('Certificate Number'),

                Forms\Components\FileUpload::make('document_file')
                    ->label('Document File')
                    ->directory('certifications')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png']),

                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'pending_verification' => 'Pending Verification',
                        'revoked' => 'Revoked',
                        'suspended' => 'Suspended',
                    ])
                    ->required()
                    ->default('pending_verification')
                    ->label('Status'),

                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->label('Notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('master.first_name')
                    ->label('Master')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Certification')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('certification_type')
                    ->label('Type')
                    ->badge()
                    ->color(function (string $state): string {
                        return $state === 'internal' ? 'success' : 'info';
                    }),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expiry Date')
                    ->date()
                    ->sortable()
                    ->color(function (string $state): string {
                        if (!$state) return 'gray';
                        $expiryDate = \Carbon\Carbon::parse($state);
                        if ($expiryDate->isPast()) return 'danger';
                        if ($expiryDate->diffInDays(now()) <= 30) return 'warning';
                        return 'success';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'active') return 'success';
                        if ($state === 'expired') return 'danger';
                        if ($state === 'pending_verification') return 'warning';
                        if ($state === 'revoked') return 'danger';
                        return 'warning';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('certification_type')
                    ->options([
                        'internal' => 'Internal',
                        'external' => 'External',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'pending_verification' => 'Pending Verification',
                        'revoked' => 'Revoked',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource\Pages\ListMakeupCertifications::route('/'),
            'create' => \Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource\Pages\CreateMakeupCertification::route('/create'),
            'edit' => \Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource\Pages\EditMakeupCertification::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Tenant\Resources;

use App\Models\AiAssistantChat;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;

class AiAssistantChatResource extends Resource
{
    protected static ?string $model = AiAssistantChat::class;
    protected static ?string $navigationGroup = 'AI Tools';

    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\Select::make('category')
                ->options([
                    'legal_accounting' => 'Legal & Accounting',
                    'marketing_content' => 'Marketing & Content',
                    'market_analysis' => 'Market Analysis'
                ])->required(),
            Forms\Components\View::make('filament.forms.components.chat-interface'),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('category')->badge(),
            Tables\Columns\TextColumn::make('request_count')->label('Requests Today'),
            Tables\Columns\TextColumn::make('quota_reset_at')->label('Next Reset')->dateTime(),
        ]);
    }
}

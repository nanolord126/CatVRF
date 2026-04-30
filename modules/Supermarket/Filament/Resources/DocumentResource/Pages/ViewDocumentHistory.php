<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Resources\DocumentResource\Pages;

use Modules\Supermarket\Domain\Models\Document;
use Modules\Supermarket\Domain\Models\DocumentHistory;
use Filament\Pages\Actions;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Contracts\View\View;

class ViewDocumentHistory extends Page
{
    protected static string $resource = DocumentResource::class;

    protected static string $view = 'filament.pages.view-document-history';

    public Document $record;

    public function mount(int $record): void
    {
        $this->record = Document::findOrFail($record);
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back')
                ->url(DocumentResource::getUrl('index')),
        ];
    }

    public function getViewData(): array
    {
        return [
            'history' => $this->record->history()->orderBy('created_at', 'desc')->get(),
        ];
    }
}

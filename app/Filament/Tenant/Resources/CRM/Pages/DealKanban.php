<?php

namespace App\Filament\Tenant\Resources\CRM\Pages;

use Filament\Resources\Pages\Page;
use App\Filament\Tenant\Resources\CRM\DealResource;
use App\Models\CRM\Pipeline;
use App\Models\CRM\Deal;

class DealKanban extends Page
{
    protected static string $resource = DealResource::class;
    protected static string $view = 'filament.tenant.resources.crm.pages.deal-kanban';

    public $pipelineId;

    public function mount()
    {
        $this->pipelineId = Pipeline::where('is_default', true)->first()?->id;
    }

    protected function getViewData(): array
    {
        $pipeline = Pipeline::with('stages.deals')->find($this->pipelineId);
        return [
            'stages' => $pipeline ? $pipeline->stages : [],
        ];
    }
}

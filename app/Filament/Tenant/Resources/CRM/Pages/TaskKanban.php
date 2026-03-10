<?php

namespace App\Filament\Tenant\Resources\CRM\Pages;

use App\Filament\Tenant\Resources\CRM\TaskResource;
use Filament\Resources\Pages\Page;

class TaskKanban extends Page
{
    protected static string $resource = TaskResource::class;

    protected static string $view = 'filament.tenant.resources.crm.pages.task-kanban';
}

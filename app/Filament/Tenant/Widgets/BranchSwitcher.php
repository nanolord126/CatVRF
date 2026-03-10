<?php
namespace App\Filament\Tenant\Widgets;
use Filament\Widgets\Widget;
use App\Models\BusinessGroup;

class BranchSwitcher extends Widget {
    protected static string $view = 'filament.tenant.widgets.branch-switcher';
    public $branches = [];
    public function mount() {
        if ($tenant = tenant()) {
            $this->branches = $tenant->businessGroup ? $tenant->businessGroup->tenants()->get() : [];
        }
    }
}
<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\WorkOrderResource;
use App\Services\ModuleManager;
use Filament\Widgets\Widget;

class QuickNewOrderWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-new-order';

    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        return ModuleManager::isActive('work_orders')
            && auth()->user()?->hasAnyRole(['administrador', 'supervisor', 'super_admin']);
    }

    public function getCreateUrl(): string
    {
        return WorkOrderResource::getUrl('create');
    }
}

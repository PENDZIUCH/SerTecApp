<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\WorkOrder;
use App\Models\Equipment;
use App\Models\Customer;
use App\Models\Visit;
use App\Filament\Resources\WorkOrderResource;
use App\Filament\Resources\EquipmentResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\VisitResource;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 2;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Órdenes Pendientes', WorkOrder::where('status', 'pending')->count())
                ->description('Órdenes por atender')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->url(WorkOrderResource::getUrl('index'))
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make('Equipos en Taller', Equipment::where('status', 'in_workshop')->count())
                ->description('En reparación')
                ->descriptionIcon('heroicon-o-wrench')
                ->color('info')
                ->url(EquipmentResource::getUrl('index'))
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make('Clientes Activos', Customer::where('is_active', true)->count())
                ->description('Total de clientes')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->url(CustomerResource::getUrl('index'))
                ->extraAttributes(['class' => 'cursor-pointer']),
            Stat::make('Visitas Hoy', Visit::whereDate('created_at', today())->count())
                ->description('Visitas registradas')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('primary')
                ->url(VisitResource::getUrl('index'))
                ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}

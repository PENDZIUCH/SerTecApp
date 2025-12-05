<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\WorkOrder;
use App\Models\Equipment;
use App\Models\Customer;
use App\Models\Visit;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Órdenes Pendientes', WorkOrder::where('status', 'pending')->count())
                ->description('Órdenes por atender')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Equipos en Taller', Equipment::where('status', 'in_repair')->count())
                ->description('En reparación')
                ->descriptionIcon('heroicon-o-wrench')
                ->color('info'),

            Stat::make('Clientes Activos', Customer::where('status', 'active')->count())
                ->description('Total de clientes')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Visitas Hoy', Visit::whereDate('created_at', today())->count())
                ->description('Visitas registradas')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('primary'),
        ];
    }
}

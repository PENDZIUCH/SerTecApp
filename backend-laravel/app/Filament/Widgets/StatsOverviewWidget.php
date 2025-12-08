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
                ->url('/admin/work-orders')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-amber-500/20 hover:-translate-y-1 active:scale-95 hover:ring-2 hover:ring-amber-500/50',
                ]),

            Stat::make('Equipos en Taller', Equipment::where('status', 'in_workshop')->count())
                ->description('En reparación')
                ->descriptionIcon('heroicon-o-wrench')
                ->color('info')
                ->url('/admin/equipment')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-blue-500/20 hover:-translate-y-1 active:scale-95 hover:ring-2 hover:ring-blue-500/50',
                ]),

            Stat::make('Clientes Activos', Customer::where('is_active', true)->count())
                ->description('Total de clientes')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->url('/admin/customers')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-green-500/20 hover:-translate-y-1 active:scale-95 hover:ring-2 hover:ring-green-500/50',
                ]),

            Stat::make('Visitas Hoy', Visit::whereDate('created_at', today())->count())
                ->description('Visitas registradas')
                ->descriptionIcon('heroicon-o-map-pin')
                ->color('primary')
                ->url('/admin/visits')
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl hover:shadow-amber-500/20 hover:-translate-y-1 active:scale-95 hover:ring-2 hover:ring-amber-500/50',
                ]),
        ];
    }
}

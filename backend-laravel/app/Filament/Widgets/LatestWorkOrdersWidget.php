<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\WorkOrder;
use App\Filament\Resources\WorkOrderResource;

class LatestWorkOrdersWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkOrder::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Nº OT')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.business_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('equipment.serial_number')
                    ->label('Equipo')
                    ->searchable()
                    ->default('Sin asignar'),
                Tables\Columns\TextColumn::make('assignedTech.name')
                    ->label('Técnico')
                    ->default('Sin asignar'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn (WorkOrder $record): string => WorkOrderResource::getUrl('view', ['record' => $record->getKey()])),
                Tables\Actions\Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->url(fn (WorkOrder $record): string => WorkOrderResource::getUrl('edit', ['record' => $record->getKey()])),
            ])
            ->recordUrl(fn (WorkOrder $record): string => WorkOrderResource::getUrl('edit', ['record' => $record]))
            ->heading('Últimas Órdenes de Trabajo');
    }
}

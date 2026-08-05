<?php

namespace App\Filament\Resources\WorkOrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\WorkPartResource;

class WorkPartsRelationManager extends RelationManager
{
    protected static string $relationship = 'workParts';
    protected static ?string $title = 'Historial de Partes';
    protected static ?string $label = 'Parte';
    protected static ?string $pluralLabel = 'Partes';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('technician'))
            ->columns([
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Técnico')
                    ->default('Sin asignar'),
                Tables\Columns\TextColumn::make('diagnosis')
                    ->label('Diagnóstico')
                    ->limit(40),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending_approval' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('supervisor_notes')
                    ->label('Nota Supervisor')
                    ->limit(40)
                    ->default('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => WorkPartResource::getUrl('view', ['record' => $record])),
            ])
            ->headerActions([])
            ->paginated(false);
    }
}

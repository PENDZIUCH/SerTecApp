<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\WorkOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Órdenes de Trabajo';
    protected static ?string $modelLabel = 'Orden de Trabajo';
    protected static ?string $pluralModelLabel = 'Órdenes de Trabajo';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')->relationship('customer', 'business_name')->label('Cliente')->required()->searchable(),
            Forms\Components\Select::make('equipment_id')->relationship('equipment', 'serial_number')->label('Equipo')->searchable(),
            Forms\Components\TextInput::make('title')->label('Título')->required(),
            Forms\Components\Textarea::make('description')->label('Descripción')->columnSpanFull(),
            Forms\Components\Select::make('priority')->label('Prioridad')
                ->options(['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente'])
                ->required(),
            Forms\Components\Select::make('status')->label('Estado')
                ->options(['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'])
                ->default('pending')->required(),
            Forms\Components\Select::make('assigned_tech_id')->relationship('assignedTech', 'name')->label('Técnico Asignado')->searchable(),
            Forms\Components\DatePicker::make('scheduled_date')->label('Fecha Programada'),
            Forms\Components\TextInput::make('labor_cost')->label('Costo Mano Obra')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('wo_number')->label('N° Orden')->searchable(),
            Tables\Columns\TextColumn::make('customer.business_name')->label('Cliente')->searchable(),
            Tables\Columns\TextColumn::make('title')->label('Título')->searchable(),
            Tables\Columns\BadgeColumn::make('priority')->label('Prioridad')
                ->colors(['success' => 'low', 'warning' => 'medium', 'danger' => 'high', 'primary' => 'urgent']),
            Tables\Columns\BadgeColumn::make('status')->label('Estado')
                ->colors(['secondary' => 'pending', 'warning' => 'in_progress', 'success' => 'completed', 'danger' => 'cancelled']),
            Tables\Columns\TextColumn::make('assignedTech.name')->label('Técnico'),
        ])
        ->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}

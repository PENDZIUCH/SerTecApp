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
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información General')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Cliente')
                        ->options(function () {
                            return \App\Models\Customer::query()
                                ->get()
                                ->mapWithKeys(fn ($customer) => [
                                    $customer->id => $customer->business_name ?: $customer->name
                                ]);
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('equipment_id', null)),
                    
                    Forms\Components\Select::make('equipment_id')
                        ->label('Equipo')
                        ->options(function (Forms\Get $get) {
                            $customerId = $get('customer_id');
                            if (!$customerId) return [];
                            
                            return \App\Models\Equipment::where('customer_id', $customerId)
                                ->with(['brand', 'model'])
                                ->get()
                                ->mapWithKeys(fn ($eq) => [
                                    $eq->id => "{$eq->brand->name} {$eq->model->name} - {$eq->serial_number}"
                                ]);
                        })
                        ->searchable()
                        ->nullable()
                        ->helperText('Opcional - dejar vacío si el técnico debe identificar el equipo en el lugar')
                        ->disabled(fn (Forms\Get $get) => !$get('customer_id')),
                    
                    Forms\Components\Select::make('assigned_tech_id')
                        ->label('Técnico Asignado')
                        ->options(function () {
                            return \App\Models\User::where('role', 'technician')
                                ->orWhere('email', 'tech@demo.com')
                                ->get()
                                ->mapWithKeys(fn ($user) => [
                                    $user->id => $user->name
                                ]);
                        })
                        ->searchable()
                        ->nullable()
                        ->helperText('Opcional - se puede asignar después'),
                    
                    Forms\Components\Select::make('priority')
                        ->label('Prioridad')
                        ->options([
                            1 => 'Baja',
                            2 => 'Media',
                            3 => 'Alta',
                            4 => 'Urgente',
                        ])
                        ->default(2)
                        ->required(),
                    
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción del Problema')
                        ->required()
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending' => 'Pendiente',
                            'in_progress' => 'En Progreso',
                            'completed' => 'Completada',
                            'cancelled' => 'Cancelada',
                        ])
                        ->default('pending')
                        ->required(),
                ])->columns(2),
            
            Forms\Components\Section::make('Repuestos Utilizados')
                ->schema([
                    Forms\Components\Repeater::make('partsUsed')
                        ->relationship('partsUsed')
                        ->schema([
                            Forms\Components\Select::make('part_id')
                                ->label('Repuesto')
                                ->relationship('part', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if ($state) {
                                        $part = \App\Models\Part::find($state);
                                        $unitCost = $part->sale_price_usd ?? 0;
                                        $set('unit_cost', $unitCost);
                                        $quantity = $get('quantity') ?? 1;
                                        $set('total_cost', $unitCost * $quantity);
                                    }
                                }),
                            
                            Forms\Components\TextInput::make('unit_cost')
                                ->label('Precio Unit.')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $quantity = $get('quantity') ?? 1;
                                    $set('total_cost', $state * $quantity);
                                }),
                            
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $unitCost = $get('unit_cost') ?? 0;
                                    $set('total_cost', $state * $unitCost);
                                }),
                            
                            Forms\Components\TextInput::make('total_cost')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->addActionLabel('Agregar repuesto')
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('N° OT')
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
                    ->searchable()
                    ->default('Sin asignar'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50),
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
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                    ]),
                
                Tables\Filters\SelectFilter::make('assigned_tech_id')
                    ->label('Técnico')
                    ->relationship('assignedTech', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_parte')
                    ->label('Ver Parte')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn (WorkOrder $record) => $record->status === 'completed')
                    ->url(fn (WorkOrder $record) => \App\Models\WorkPart::where('work_order_id', $record->id)->exists() 
                        ? route('filament.admin.resources.work-parts.view', [
                            'record' => \App\Models\WorkPart::where('work_order_id', $record->id)->first()->id
                        ])
                        : null
                    ),
                
                Tables\Actions\EditAction::make(),
            ])
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

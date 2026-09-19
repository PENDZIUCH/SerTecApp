<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Filament\Resources\WorkPartResource;
use App\Models\WorkOrder;
use App\Services\ModuleManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkOrderResource extends Resource
{
    public static function canAccess(): bool
    {
        return ModuleManager::isActive('work_orders');
    }

    protected static ?string $model = WorkOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Órdenes de Trabajo';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Orden de Trabajo';
    protected static ?string $pluralModelLabel = 'Órdenes de Trabajo';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información General')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('Cliente')
                        ->options(function () {
                            // Bug real encontrado 2026-09-18: usaba $customer->name,
                            // que no existe como atributo (Customer no tiene columna
                            // "name") - para un cliente individual sin business_name
                            // el label salia null y rompia el Select entero.
                            return \App\Models\Customer::query()
                                ->get()
                                ->mapWithKeys(fn ($customer) => [
                                    $customer->id => $customer->business_name ?: $customer->full_name ?: "Cliente #{$customer->id}"
                                ]);
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            $set('equipment_id', null);
                            $set('contact_email', $state ? \App\Models\Customer::find($state)?->email : null);
                        }),

                    // No es columna de work_orders - $fillable de WorkOrder no
                    // la incluye, así que Create/EditRecord la ignora sola al
                    // guardar el modelo (no hace falta dehydrated(false); de
                    // hecho lo rompe: Filament borra el campo de
                    // getState() si no está dehidratado, y afterCreate()/
                    // afterSave() lo necesitan para mandar el email y
                    // actualizar el cliente). Es el email al que se manda el
                    // aviso de "orden creada" y, si se edita acá, el que queda
                    // guardado como el email vigente del cliente. Pedido de
                    // Hugo (2026-09-18): antes el email se mandaba a ciegas al
                    // que hubiera en la ficha del cliente, sin mostrarlo ni
                    // poder corregirlo - encontró un caso real donde casi se
                    // manda a un email viejo/de prueba.
                    Forms\Components\TextInput::make('contact_email')
                        ->label('Email de contacto (aviso al cliente)')
                        ->email()
                        ->live()
                        ->helperText(fn (Forms\Get $get) => $get('contact_email')
                            ? 'Se le va a avisar a esta dirección. Si la cambiás, queda como el email del cliente de ahora en más.'
                            : 'El cliente no tiene email cargado — no se le va a enviar ningún aviso. Podés cargarlo acá.')
                        ->afterStateHydrated(function (Forms\Set $set, ?WorkOrder $record) {
                            if ($record) {
                                $set('contact_email', $record->customer?->email);
                            }
                        })
                        ->columnSpanFull(),

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
                            return \App\Models\User::role('técnico')
                                ->orWhere('email', 'tech@demo.com')
                                ->get()
                                ->mapWithKeys(fn ($user) => [
                                    $user->id => $user->name
                                ]);
                        })
                        ->searchable()
                        ->required()
                        ->helperText('Requerido — asignar un técnico antes de enviar'),

                    // Agenda (2026-09-19): con técnico + fecha cargados acá,
                    // se genera/actualiza sola una Visita en Agenda para ese
                    // día (WorkOrderService::syncBooking, ver
                    // CreateWorkOrder/EditWorkOrder afterCreate/afterSave) -
                    // mismo comportamiento que ya tenía "Nueva Orden" en el
                    // admin de la PWA, ahora también acá. Los tres campos son
                    // opcionales: una orden puede quedar sin agendar y
                    // agendarse después con "Armar Recorrido".
                    Forms\Components\Group::make([
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label('Fecha programada')
                            ->helperText('Con técnico + fecha, se agenda sola en Agenda.'),
                        Forms\Components\TimePicker::make('scheduled_time')
                            ->label('Hora programada'),
                        Forms\Components\TextInput::make('estimated_duration_minutes')
                            ->label('Duración estimada (min)')
                            ->numeric()
                            ->default(60)
                            ->helperText('Para calcular el fin de la visita en Agenda.'),
                    ])->columns(3)->columnSpanFull(),

                    Forms\Components\Select::make('priority')
                        ->label('Prioridad')
                        ->options([
                            'low'    => 'Baja',
                            'medium' => 'Media',
                            'high'   => 'Alta',
                            'urgent' => 'Urgente',
                        ])
                        ->default('medium')
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
                        ->label('Repuestos')
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
                                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Prioridad')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'low'    => 'Baja',
                        'medium' => 'Media',
                        'high'   => 'Alta',
                        'urgent' => 'Urgente',
                        default  => $state,
                    })
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger'  => fn ($state) => in_array($state, ['high', 'urgent']),
                    ]),
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
            ->recordUrl(fn (WorkOrder $record): string => WorkOrderResource::getUrl('edit', ['record' => $record]))
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
                        ? WorkPartResource::getUrl('view', [
                            'record' => \App\Models\WorkPart::where('work_order_id', $record->id)->first()->id
                        ])
                        : null
                    ),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\WorkOrderResource\RelationManagers\WorkPartsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'view' => Pages\ViewWorkOrder::route('/{record}'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}

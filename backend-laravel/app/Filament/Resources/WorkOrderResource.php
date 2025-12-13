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
                        ->relationship('customer', 'business_name')
                        ->searchable()
                        ->preload()
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
                        ->required()
                        ->disabled(fn (Forms\Get $get) => !$get('customer_id')),
                    
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción del Problema')
                        ->required()
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'open' => 'Abierta',
                            'closed' => 'Cerrada',
                        ])
                        ->default('open')
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
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $part = \App\Models\Part::find($state);
                                        $set('unit_price', $part->sale_price_usd ?? 0);
                                    }
                                }),
                            
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $unitPrice = $get('unit_price') ?? 0;
                                    $set('subtotal', $state * $unitPrice);
                                }),
                            
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Precio Unit.')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('subtotal')
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state === 'open' ? 'Abierta' : 'Cerrada')
                    ->colors([
                        'warning' => 'open',
                        'success' => 'closed',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
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

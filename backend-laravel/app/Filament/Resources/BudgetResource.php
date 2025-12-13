<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BudgetResource\Pages;
use App\Models\Budget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Presupuestos';
    protected static ?int $navigationSort = 1;

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
                        ->required(),
                    
                    Forms\Components\TextInput::make('title')
                        ->label('Título')
                        ->default('Presupuesto')
                        ->required(),
                    
                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Válido hasta')
                        ->default(now()->addDays(30)),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->columnSpanFull(),
                ])->columns(3),
            
            Forms\Components\Section::make('Items')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Forms\Components\Select::make('item_type')
                                ->label('Tipo')
                                ->options([
                                    'part' => 'Repuesto',
                                    'service' => 'Servicio',
                                ])
                                ->default('part')
                                ->required()
                                ->live(),
                            
                            Forms\Components\Select::make('part_id')
                                ->label('Repuesto')
                                ->relationship('part', 'name')
                                ->searchable()
                                ->preload()
                                ->visible(fn (Forms\Get $get) => $get('item_type') === 'part')
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if ($state) {
                                        $part = \App\Models\Part::find($state);
                                        $set('description', $part->name);
                                        $set('unit_price', $part->sale_price_usd ?? 0);
                                        $quantity = $get('quantity') ?? 1;
                                        $set('total', ($part->sale_price_usd ?? 0) * $quantity);
                                    }
                                }),
                            
                            Forms\Components\TextInput::make('description')
                                ->label('Descripción')
                                ->required(),
                            
                            Forms\Components\TextInput::make('quantity')
                                ->label('Cantidad')
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $unitPrice = $get('unit_price') ?? 0;
                                    $set('total', $state * $unitPrice);
                                }),
                            
                            Forms\Components\TextInput::make('unit_price')
                                ->label('Precio Unit.')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $quantity = $get('quantity') ?? 1;
                                    $set('total', $state * $quantity);
                                }),
                            
                            Forms\Components\TextInput::make('total')
                                ->label('Total')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(),
                        ])
                        ->columns(6)
                        ->defaultItems(1)
                        ->addActionLabel('Agregar ítem')
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('N°')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.business_name')
                    ->label('Cliente')
                    ->getStateUsing(fn ($record) => $record->customer->business_name ?: $record->customer->name)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviado',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    })
                    ->colors([
                        'secondary' => 'draft',
                        'primary' => 'sent',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => route('budgets.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->hasRole('admin')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}

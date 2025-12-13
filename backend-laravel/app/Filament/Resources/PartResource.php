<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PartResource\Pages;
use App\Models\Part;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PartResource extends Resource
{
    protected static ?string $model = Part::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Repuestos';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Repuesto')
                ->schema([
                    Forms\Components\TextInput::make('part_number')
                        ->label('N° de Parte')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('equipment_model_id')
                        ->label('Modelo de Equipo')
                        ->relationship('equipmentModel', 'name')
                        ->searchable()
                        ->preload(),
                ])->columns(2),
            
            Forms\Components\Section::make('Inventario')
                ->schema([
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Stock Actual')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Forms\Components\TextInput::make('min_stock_level')
                        ->label('Stock Mínimo')
                        ->numeric()
                        ->default(0),
                    Forms\Components\TextInput::make('location')
                        ->label('Ubicación')
                        ->placeholder('CAJA, Estante 3, etc'),
                ])->columns(3),
            
            Forms\Components\Section::make('Precios')
                ->schema([
                    Forms\Components\TextInput::make('fob_price_usd')
                        ->label('Precio FOB (USD)')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $markup = $get('markup_percent') ?? 20;
                            if ($state) {
                                $salePrice = $state * (1 + ($markup / 100));
                                $set('sale_price_usd', round($salePrice, 2));
                            }
                        }),
                    Forms\Components\TextInput::make('markup_percent')
                        ->label('Markup (%)')
                        ->numeric()
                        ->default(20)
                        ->suffix('%')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $fob = $get('fob_price_usd');
                            if ($fob && $state) {
                                $salePrice = $fob * (1 + ($state / 100));
                                $set('sale_price_usd', round($salePrice, 2));
                            }
                        }),
                    Forms\Components\TextInput::make('sale_price_usd')
                        ->label('Precio Venta (USD)')
                        ->numeric()
                        ->prefix('$')
                        ->disabled()
                        ->dehydrated(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('part_number')
                    ->label('N° Parte')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('equipmentModel.name')
                    ->label('Modelo')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('fob_price_usd')
                    ->label('FOB')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_price_usd')
                    ->label('Venta')
                    ->money('USD')
                    ->sortable(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->hasRole('admin')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParts::route('/'),
            'create' => Pages\CreatePart::route('/create'),
            'edit' => Pages\EditPart::route('/{record}/edit'),
        ];
    }
}

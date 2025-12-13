<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EquipmentResource\Pages;
use App\Models\Equipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Equipos';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')
                ->label('Cliente')
                ->relationship('customer', 'business_name', fn ($query) => $query->whereNotNull('business_name'))
                ->searchable()
                ->preload()
                ->required(),
            
            Forms\Components\Select::make('brand_id')
                ->label('Marca')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')->required(),
                ]),
            
            Forms\Components\Select::make('model_id')
                ->label('Modelo')
                ->relationship('model', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('brand_id')
                        ->relationship('brand', 'name')
                        ->required(),
                ]),
            
            Forms\Components\TextInput::make('serial_number')
                ->label('Número de Serie')
                ->required(),
            
            Forms\Components\Textarea::make('notes')
                ->label('Notas')
                ->columnSpanFull(),
            
            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.business_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model.name')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('N° Serie')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipment::route('/'),
            'create' => Pages\CreateEquipment::route('/create'),
            'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }
}

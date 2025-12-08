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
    protected static ?string $modelLabel = 'Equipo';
    protected static ?string $pluralModelLabel = 'Equipos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')->relationship('customer', 'business_name')->label('Cliente')->required()->searchable(),
            Forms\Components\Select::make('brand_id')->relationship('brand', 'name')->label('Marca')->searchable(),
            Forms\Components\Select::make('model_id')->relationship('model', 'name')->label('Modelo')->searchable(),
            Forms\Components\TextInput::make('serial_number')->label('N° Serie'),
            Forms\Components\TextInput::make('equipment_code')->label('Código'),
            Forms\Components\DatePicker::make('purchase_date')->label('Fecha Compra'),
            Forms\Components\DatePicker::make('warranty_expiration')->label('Vencimiento Garantía'),
            Forms\Components\DatePicker::make('next_service_date')->label('Próximo Service'),
            Forms\Components\TextInput::make('location')->label('Ubicación'),
            Forms\Components\Select::make('status')->label('Estado')
                ->options(['active' => 'Activo', 'inactive' => 'Inactivo', 'in_workshop' => 'En Taller', 'decommissioned' => 'Dado de Baja'])
                ->default('active')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('customer.business_name')->label('Cliente')->searchable(),
            Tables\Columns\TextColumn::make('brand.name')->label('Marca'),
            Tables\Columns\TextColumn::make('serial_number')->label('N° Serie')->searchable(),
            Tables\Columns\BadgeColumn::make('status')->label('Estado')
                ->colors(['success' => 'active', 'warning' => 'in_workshop', 'danger' => 'inactive']),
            Tables\Columns\TextColumn::make('location')->label('Ubicación'),
        ])
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

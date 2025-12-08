<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopItemResource\Pages;
use App\Models\WorkshopItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkshopItemResource extends Resource
{
    protected static ?string $model = WorkshopItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Taller';
    protected static ?string $modelLabel = 'Item de Taller';
    protected static ?string $pluralModelLabel = 'Items de Taller';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('equipment_id')->relationship('equipment', 'serial_number')->label('Equipo')->required()->searchable(),
            Forms\Components\Select::make('customer_id')->relationship('customer', 'business_name')->label('Cliente')->required()->searchable(),
            Forms\Components\Select::make('status')->label('Estado')
                ->options(['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completado'])
                ->default('pending'),
            Forms\Components\DatePicker::make('entry_date')->label('Fecha Ingreso')->required(),
            Forms\Components\DatePicker::make('estimated_completion_date')->label('Fecha Estimada Finalización'),
            Forms\Components\Select::make('assigned_tech_id')->relationship('assignedTech', 'name')->label('Técnico')->searchable(),
            Forms\Components\Textarea::make('description')->label('Descripción'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('equipment.serial_number')->label('Equipo')->searchable(),
            Tables\Columns\TextColumn::make('customer.business_name')->label('Cliente')->searchable(),
            Tables\Columns\BadgeColumn::make('status')->label('Estado'),
            Tables\Columns\TextColumn::make('entry_date')->label('Ingreso')->date(),
            Tables\Columns\TextColumn::make('estimated_completion_date')->label('Est. Finalización')->date(),
        ])
        ->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkshopItems::route('/'),
            'create' => Pages\CreateWorkshopItem::route('/create'),
            'edit' => Pages\EditWorkshopItem::route('/{record}/edit'),
        ];
    }
}

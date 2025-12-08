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
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Repuestos';
    protected static ?string $modelLabel = 'Repuesto';
    protected static ?string $pluralModelLabel = 'Repuestos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            Forms\Components\TextInput::make('sku')->label('SKU')->unique(ignoreRecord: true)->maxLength(100),
            Forms\Components\Textarea::make('description')->label('Descripción'),
            Forms\Components\TextInput::make('unit_cost')->label('Costo Unitario')->numeric()->required(),
            Forms\Components\TextInput::make('stock_qty')->label('Stock')->numeric()->required(),
            Forms\Components\TextInput::make('min_stock_level')->label('Stock Mínimo')->numeric()->required(),
            Forms\Components\Toggle::make('is_active')->label('Activo')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
            Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable(),
            Tables\Columns\TextColumn::make('unit_cost')->label('Precio')->money('ARS'),
            Tables\Columns\TextColumn::make('stock_qty')->label('Stock')->sortable(),
            Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean(),
        ])
        ->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
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

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
    protected static ?string $modelLabel = 'Presupuesto';
    protected static ?string $pluralModelLabel = 'Presupuestos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')->relationship('customer', 'business_name')->label('Cliente')->required()->searchable(),
            Forms\Components\TextInput::make('title')->label('Título')->required(),
            Forms\Components\Select::make('status')->label('Estado')
                ->options(['draft' => 'Borrador', 'sent' => 'Enviado', 'approved' => 'Aprobado', 'rejected' => 'Rechazado'])
                ->default('draft'),
            Forms\Components\DatePicker::make('valid_until')->label('Válido Hasta'),
            Forms\Components\TextInput::make('tax_percent')->label('IVA %')->numeric()->default(21),
            Forms\Components\Select::make('discount_type')->label('Tipo Descuento')
                ->options(['none' => 'Ninguno', 'percent' => 'Porcentaje', 'amount' => 'Monto Fijo'])
                ->default('none'),
            Forms\Components\TextInput::make('discount_value')->label('Valor Descuento')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('customer.business_name')->label('Cliente')->searchable(),
            Tables\Columns\TextColumn::make('title')->label('Título')->searchable(),
            Tables\Columns\BadgeColumn::make('status')->label('Estado'),
            Tables\Columns\TextColumn::make('total_amount')->label('Total')->money('ARS'),
        ])
        ->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
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

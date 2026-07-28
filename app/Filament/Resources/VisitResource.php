<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Models\Visit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VisitResource extends Resource
{
    protected static ?string $model = Visit::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Visitas';
    protected static ?string $modelLabel = 'Visita';
    protected static ?string $pluralModelLabel = 'Visitas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('work_order_id')->relationship('workOrder', 'wo_number')->label('Orden de Trabajo')->required()->searchable(),
            Forms\Components\Select::make('assigned_tech_id')->relationship('assignedTech', 'name')->label('Técnico')->searchable(),
            Forms\Components\DatePicker::make('visit_date')->label('Fecha')->required(),
            Forms\Components\TimePicker::make('scheduled_time')->label('Hora'),
            Forms\Components\TextInput::make('estimated_duration_minutes')->label('Duración Estimada (min)')->numeric(),
            Forms\Components\Select::make('status')->label('Estado')
                ->options(['scheduled' => 'Programada', 'in_progress' => 'En Progreso', 'completed' => 'Completada'])
                ->default('scheduled'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('workOrder.wo_number')->label('N° Orden')->searchable(),
            Tables\Columns\TextColumn::make('assignedTech.name')->label('Técnico'),
            Tables\Columns\TextColumn::make('visit_date')->label('Fecha')->date(),
            Tables\Columns\BadgeColumn::make('status')->label('Estado'),
        ])
        ->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }
}

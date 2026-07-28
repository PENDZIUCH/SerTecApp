<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Suscripciones';
    protected static ?string $modelLabel = 'Suscripción';
    protected static ?string $pluralModelLabel = 'Suscripciones';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_id')->relationship('customer', 'business_name')->label('Cliente')->required()->searchable(),
            Forms\Components\TextInput::make('plan_name')->label('Plan')->required(),
            Forms\Components\TextInput::make('visits_per_period')->label('Visitas por Período')->numeric()->required(),
            Forms\Components\Select::make('billing_cycle')->label('Ciclo Facturación')
                ->options(['monthly' => 'Mensual', 'quarterly' => 'Trimestral', 'yearly' => 'Anual'])
                ->required(),
            Forms\Components\DatePicker::make('renewal_date')->label('Fecha Renovación')->required(),
            Forms\Components\Toggle::make('auto_renew')->label('Renovación Automática')->default(true),
            Forms\Components\Select::make('status')->label('Estado')
                ->options(['active' => 'Activa', 'suspended' => 'Suspendida', 'cancelled' => 'Cancelada'])
                ->default('active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('customer.business_name')->label('Cliente')->searchable(),
            Tables\Columns\TextColumn::make('plan_name')->label('Plan'),
            Tables\Columns\TextColumn::make('visits_per_period')->label('Visitas'),
            Tables\Columns\BadgeColumn::make('status')->label('Estado'),
            Tables\Columns\TextColumn::make('renewal_date')->label('Renovación')->date(),
        ])
        ->actions([Tables\Actions\EditAction::make()])
        ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}

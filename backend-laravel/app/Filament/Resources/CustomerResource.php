<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('customer_type')
                ->label('Tipo')
                ->options(['individual' => 'Individual', 'company' => 'Empresa', 'gym' => 'Gimnasio'])
                ->required(),
            Forms\Components\TextInput::make('business_name')
                ->label('Razón Social')
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $set, $livewire) {
                    if (empty($state)) return;
                    
                    $recordId = $livewire->record->id ?? null;
                    
                    $exists = \App\Models\Customer::where('id', '!=', $recordId)
                        ->where('business_name', $state)
                        ->exists();
                    
                    if ($exists) {
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Razón Social duplicada')
                            ->body("Ya existe un cliente con la razón social '{$state}'. Verifica que no sea el mismo cliente.")
                            ->persistent()
                            ->send();
                    }
                }),
            Forms\Components\TextInput::make('first_name')
                ->label('Nombre'),
            Forms\Components\TextInput::make('last_name')
                ->label('Apellido'),
            Forms\Components\TextInput::make('email')
                ->email()
                ->label('Email')
                ->unique(ignoreRecord: true)
                ->rules([
                    function ($livewire) {
                        return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                            if (empty($value)) return;
                            
                            $recordId = $livewire->record->id ?? null;
                            
                            // Buscar si el email existe en primary O secondary de otros registros
                            $exists = \App\Models\Customer::where('id', '!=', $recordId)
                                ->where(function ($query) use ($value) {
                                    $query->where('email', $value)
                                          ->orWhere('secondary_email', $value);
                                })
                                ->exists();
                            
                            if ($exists) {
                                $fail('Este email ya está registrado en otro cliente (como email principal o secundario).');
                            }
                        };
                    },
                ])
                ->validationMessages([
                    'unique' => 'Este email ya está registrado en otro cliente.',
                ]),
            Forms\Components\TextInput::make('secondary_email')
                ->email()
                ->label('Email Secundario')
                ->different('email')
                ->rules([
                    function ($livewire) {
                        return function (string $attribute, $value, \Closure $fail) use ($livewire) {
                            if (empty($value)) return;
                            
                            $recordId = $livewire->record->id ?? null;
                            
                            // Buscar si el email existe en primary O secondary de otros registros
                            $exists = \App\Models\Customer::where('id', '!=', $recordId)
                                ->where(function ($query) use ($value) {
                                    $query->where('email', $value)
                                          ->orWhere('secondary_email', $value);
                                })
                                ->exists();
                            
                            if ($exists) {
                                $fail('Este email ya está registrado en otro cliente (como email principal o secundario).');
                            }
                        };
                    },
                ])
                ->validationMessages([
                    'different' => 'El email secundario debe ser diferente del email principal.',
                ]),
            Forms\Components\TextInput::make('phone')
                ->tel()
                ->label('Teléfono'),
            Forms\Components\TextInput::make('tax_id')
                ->label('CUIT/CUIL'),
            Forms\Components\Textarea::make('address')
                ->label('Dirección')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('city')
                ->label('Ciudad'),
            Forms\Components\Select::make('state')
                ->label('Provincia')
                ->options([
                    'Buenos Aires' => 'Buenos Aires',
                    'CABA' => 'Ciudad Autónoma de Buenos Aires',
                    'Catamarca' => 'Catamarca',
                    'Chaco' => 'Chaco',
                    'Chubut' => 'Chubut',
                    'Córdoba' => 'Córdoba',
                    'Corrientes' => 'Corrientes',
                    'Entre Ríos' => 'Entre Ríos',
                    'Formosa' => 'Formosa',
                    'Jujuy' => 'Jujuy',
                    'La Pampa' => 'La Pampa',
                    'La Rioja' => 'La Rioja',
                    'Mendoza' => 'Mendoza',
                    'Misiones' => 'Misiones',
                    'Neuquén' => 'Neuquén',
                    'Río Negro' => 'Río Negro',
                    'Salta' => 'Salta',
                    'San Juan' => 'San Juan',
                    'San Luis' => 'San Luis',
                    'Santa Cruz' => 'Santa Cruz',
                    'Santa Fe' => 'Santa Fe',
                    'Santiago del Estero' => 'Santiago del Estero',
                    'Tierra del Fuego' => 'Tierra del Fuego',
                    'Tucumán' => 'Tucumán',
                ])
                ->searchable()
                ->native(false),
            Forms\Components\TextInput::make('country')
                ->label('País')
                ->default('Argentina'),
            Forms\Components\TextInput::make('postal_code')
                ->label('Código Postal'),
            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_type')->label('Tipo')->badge()->sortable(),
                Tables\Columns\TextColumn::make('business_name')->label('Razón Social')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('first_name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Teléfono')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->emptyStateHeading('No hay clientes')
            ->emptyStateDescription('Crea tu primer cliente o importa desde Excel')
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}

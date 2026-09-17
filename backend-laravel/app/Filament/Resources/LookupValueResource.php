<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LookupValueResource\Pages;
use App\Models\LookupValue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Administra las listas configurables genericas (ver App\Models\LookupValue
// para el porque). Hoy solo hay una category en uso ('customer_type'), pero
// la pantalla ya soporta cualquier otra que se agregue a futuro sin tocar
// este archivo - alcanza con crear filas con una category nueva.
class LookupValueResource extends Resource
{
    protected static ?string $model = LookupValue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Listas configurables';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 87;
    protected static ?string $modelLabel = 'valor';
    protected static ?string $pluralModelLabel = 'Listas configurables';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['administrador', 'supervisor', 'super_admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category')
                    ->label('Lista')
                    ->options([
                        'customer_type' => 'Tipo de Cliente',
                    ])
                    // Permite tipear una category nueva que todavia no
                    // exista en la lista de arriba, sin tocar codigo.
                    ->createOptionForm([
                        Forms\Components\TextInput::make('value')->label('Nombre de la lista nueva')->required(),
                    ])
                    ->createOptionUsing(fn (array $data) => $data['value'])
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->label('Valor interno')
                    ->helperText('Identificador corto, sin espacios ni tildes (ej: hotel). No se muestra al usuario.')
                    ->required()
                    ->maxLength(100)
                    ->rule('regex:/^[a-z0-9_]+$/')
                    ->validationMessages(['regex' => 'Solo minúsculas, números y guión bajo, sin espacios.']),
                Forms\Components\TextInput::make('label')
                    ->label('Texto a mostrar')
                    ->helperText('Lo que ve el usuario en el desplegable (ej: Hotel).')
                    ->required()
                    ->maxLength(150),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->helperText('Si lo apagás, deja de aparecer como opción para elegir en registros nuevos — los que ya lo tenían asignado no se tocan.')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->helperText('Menor número aparece primero en el desplegable.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Lista')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'customer_type' => 'Tipo de Cliente',
                        default => $state,
                    })
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Texto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor interno')
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Lista')
                    ->options([
                        'customer_type' => 'Tipo de Cliente',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle')
                    ->label(fn (LookupValue $record) => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (LookupValue $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (LookupValue $record) => $record->is_active ? 'gray' : 'success')
                    ->action(fn (LookupValue $record) => $record->update(['is_active' => ! $record->is_active])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLookupValues::route('/'),
        ];
    }
}

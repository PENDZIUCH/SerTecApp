<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Personal')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel()
                        ->placeholder('+54 11 1234-5678')
                        ->maxLength(20)
                        ->helperText('Formato: +54 11 xxxx-xxxx'),
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->default(fn () => \Illuminate\Support\Str::random(12))
                        ->helperText('Contraseña generada automáticamente. Puedes cambiarla.'),
                ])->columns(2),

            Forms\Components\Section::make('Rol y Permisos')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Rol')
                        ->relationship('roles', 'name')
                        ->options(Role::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Selecciona el rol del usuario (administrador, técnico, supervisor, cliente)'),
                ]),

            Forms\Components\Section::make('Estado')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Usuario Activo')
                        ->default(true)
                        ->helperText('Usuarios inactivos no pueden iniciar sesión'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copiado'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ?? '-'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Rol')
                    ->badge()
                    ->colors([
                        'danger' => 'administrador',
                        'warning' => 'supervisor',
                        'success' => 'técnico',
                        'info' => 'cliente',
                    ])
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('reset_password')
                    ->label('Nueva Clave')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Generar Nueva Contraseña')
                    ->modalDescription('Se generará una contraseña aleatoria.')
                    ->action(function (User $record) {
                        $newPassword = \Illuminate\Support\Str::random(12);
                        $record->password = \Illuminate\Support\Facades\Hash::make($newPassword);
                        $record->save();
                        
                        // Generar token Base64 para auto-login
                        $credentials = "{$record->email}:{$newPassword}";
                        $token = base64_encode($credentials);
                        $autoLoginUrl = "https://pro.pendziuch.com/l?t={$token}";
                        
                        // Preparar WhatsApp link
                        $phone = preg_replace('/[^0-9]/', '', $record->phone);
                        if (!str_starts_with($phone, '54')) {
                            if (str_starts_with($phone, '11')) {
                                $phone = '54' . $phone;
                            } elseif (str_starts_with($phone, '9')) {
                                $phone = '54' . $phone;
                            } else {
                                $phone = '549' . $phone;
                            }
                        }
                        
                        $whatsappMessage = urlencode(
                            "Hola {$record->name}!\n\n" .
                            "Tu acceso a la app de Fitness Company:\n\n" .
                            "{$autoLoginUrl}\n\n" .
                            "(Guarda este link para acceder siempre)"
                        );
                        $whatsappUrl = "https://wa.me/{$phone}?text={$whatsappMessage}";
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Contraseña actualizada')
                            ->body("Nueva contraseña: **{$newPassword}**\n\nLink de acceso: {$autoLoginUrl}")
                            ->success()
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('copy')
                                    ->label('Copiar Link')
                                    ->button()
                                    ->color('gray')
                                    ->extraAttributes([
                                        'x-on:click' => "navigator.clipboard.writeText('{$autoLoginUrl}'); \$tooltip('Link copiado!', { timeout: 2000 })"
                                    ]),
                                \Filament\Notifications\Actions\Action::make('whatsapp')
                                    ->label('Enviar WhatsApp')
                                    ->button()
                                    ->color('success')
                                    ->url($whatsappUrl)
                                    ->openUrlInNewTab()
                                    ->visible(!empty($record->phone)),
                            ])
                            ->send();
                    }),
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn (User $record) => !empty($record->phone))
                    ->url(function (User $record) {
                        // Limpiar el telefono dejando solo numeros
                        $phone = preg_replace('/[^0-9]/', '', $record->phone);
                        
                        // Formato Argentina:
                        // Fijos CABA/GBA: 5411 + numero (ej: 541112345678)
                        // Celulares: 549 + codigo area + numero (ej: 5491112345678)
                        
                        if (!str_starts_with($phone, '54')) {
                            // Si empieza con 11 (fijo CABA)
                            if (str_starts_with($phone, '11')) {
                                $phone = '54' . $phone;
                            }
                            // Si empieza con 9 (celular con 9 adelante)
                            elseif (str_starts_with($phone, '9')) {
                                $phone = '54' . $phone;
                            }
                            // Otros casos (asumir celular sin 9)
                            else {
                                $phone = '549' . $phone;
                            }
                        }
                        
                        $message = urlencode(
                            "Hola {$record->name}!\n\n" .
                            "Tu acceso a la app de Fitness Company:\n\n" .
                            "Usuario: {$record->email}\n" .
                            "Contrasena: (solicitar al supervisor)\n\n" .
                            "Descargar app: https://pro.pendziuch.com"
                        );
                        return "https://wa.me/{$phone}?text={$message}";
                    })
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== 1)
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            // Prevenir borrado del super admin (ID 1)
                            $records->filter(fn ($record) => $record->id !== 1)->each->delete();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('roles');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole('administrador');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('administrador');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->hasRole('administrador');
    }

    public static function canDelete($record): bool
    {
        // No permitir borrar super admin (primer usuario)
        if ($record->id === 1) {
            return false;
        }
        return auth()->user()->hasRole('administrador');
    }
}

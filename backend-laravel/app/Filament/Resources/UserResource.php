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
    protected static ?int $navigationSort = 98;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información Personal')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')->email()->required()
                        ->unique(ignoreRecord: true)->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')->tel()
                        ->placeholder('+54 11 1234-5678')->maxLength(20),
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')->password()->revealable()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->default(fn () => \Illuminate\Support\Str::random(12)),
                ])->columns(2),

            Forms\Components\Section::make('Rol y Permisos')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Rol')
                        ->relationship('roles', 'name')
                        ->options(function () {
                            $query = Role::query();
                            if (auth()->check() && auth()->user()->hasRole('supervisor')) {
                                return $query->whereIn('name', ['técnico', 'tecnico'])->pluck('name', 'id');
                            }
                            // Solo un super_admin puede ver/asignar el rol super_admin.
                            // No confiar solo en esto: ver la regla de validacion mas abajo.
                            if (!auth()->check() || !auth()->user()->hasRole('super_admin')) {
                                $query->where('name', '!=', 'super_admin');
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->rule(function () {
                            return function (string $attribute, $value, \Closure $fail) {
                                if (auth()->check() && auth()->user()->hasRole('super_admin')) {
                                    return;
                                }
                                if (Role::find($value)?->name === 'super_admin') {
                                    $fail('Solo un usuario con rol super_admin puede asignar el rol super_admin.');
                                }
                            };
                        })
                        ->searchable()->preload()->required(),
                ]),

            Forms\Components\Section::make('Estado')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Usuario Activo')->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('phone')->label('Teléfono')->searchable()->toggleable()
                    ->formatStateUsing(fn ($state) => $state ?? '-'),
                Tables\Columns\TextColumn::make('roles.name')->label('Rol')->badge()
                    ->colors(['danger' => 'administrador', 'warning' => 'supervisor', 'success' => 'técnico', 'info' => 'cliente'])
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')->label('Activo')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')
                    ->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('roles')->label('Rol')->relationship('roles', 'name')->preload(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Estado')->boolean()
                    ->trueLabel('Solo activos')->falseLabel('Solo inactivos')->native(false),
            ])
            ->actions([
                Tables\Actions\Action::make('reset_password')
                    ->label('Enviar Acceso')->icon('heroicon-o-key')->color('warning')
                    ->visible(fn (User $record) => !static::isProtectedSuperAdmin($record)
                        && auth()->user()->hasAnyRole(['administrador', 'super_admin', 'supervisor']))
                    ->requiresConfirmation()
                    ->modalHeading('Generar y enviar acceso')
                    ->modalDescription('Se generará una contraseña temporal y un magic link para este usuario.')
                    ->action(function (User $record) {
                        // Generar contraseña temporal
                        $tempPass = \Illuminate\Support\Str::random(8);
                        $record->update(['password' => \Illuminate\Support\Facades\Hash::make($tempPass)]);

                        // Magic link PWA
                        $token = $record->createToken('magic-link', ['*'], now()->addDays(365))->plainTextToken;
                        $pwaUrl = config('app.pwa_url', 'https://sertecapp.pendziuch.com');
                        $magicLink = $pwaUrl . '/l?t=' . $token;

                        // WhatsApp
                        $phone = preg_replace('/[^0-9]/', '', $record->phone ?? '');
                        if (!empty($phone)) {
                            if (!str_starts_with($phone, '54')) {
                                $phone = str_starts_with($phone, '11') ? '54' . $phone : '549' . $phone;
                            }
                            $msg = urlencode(
                                "Hola {$record->name}!\n\n" .
                                "Tus datos de acceso a la app SerTecApp:\n\n" .
                                "Email: {$record->email}\n" .
                                "Contraseña: {$tempPass}\n\n" .
                                "Página para ingresar con usuario y contraseña:\n{$pwaUrl}\n\n" .
                                "O si preferís, acceso directo sin escribir nada (un solo clic):\n{$magicLink}\n\n" .
                                "Tip: si usás siempre el mismo dispositivo, el sistema te va a recordar automáticamente."
                            );
                            $whatsappUrl = "https://wa.me/{$phone}?text={$msg}";
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Acceso generado para ' . $record->name)
                            ->body("Email: {$record->email} | Pass: {$tempPass}")
                            ->success()->persistent()
                            ->actions(array_filter([
                                \Filament\Notifications\Actions\Action::make('copy_link')
                                    ->label('Copiar magic link')->button()->color('gray')
                                    ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{$magicLink}'); \$tooltip('Copiado!', { timeout: 2000 })"]),
                                !empty($record->phone) ? \Filament\Notifications\Actions\Action::make('whatsapp')
                                    ->label('Enviar por WhatsApp')->button()->color('success')
                                    ->url($whatsappUrl ?? '#')->openUrlInNewTab() : null,
                                !empty($record->email) ? \Filament\Notifications\Actions\Action::make('email_acceso')
                                    ->label('Enviar por Email')->button()->color('info')
                                    ->action(function () use ($record, $magicLink, $tempPass) {
                                        try {
                                            \Illuminate\Support\Facades\Mail::to($record->email)
                                                ->send(new \App\Mail\AccesoUsuarioMail($record, $magicLink, $tempPass));
                                            \Filament\Notifications\Notification::make()
                                                ->title('Email enviado a ' . $record->email)->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Error al enviar')->body($e->getMessage())->danger()->send();
                                        }
                                    }) : null,
                            ]))
                            ->send();
                    }),

                Tables\Actions\Action::make('enviar_whatsapp')
                    ->label('Enviar por WhatsApp')->link()->color('success')
                    ->visible(fn (User $record) => !empty($record->phone) && !static::isProtectedSuperAdmin($record)
                        && auth()->user()->hasAnyRole(['administrador', 'super_admin', 'supervisor']))
                    ->requiresConfirmation()
                    ->modalDescription('Se generará una contraseña temporal y se enviará por WhatsApp junto con el magic link.')
                    ->action(function (User $record) {
                        $tempPass = \Illuminate\Support\Str::random(8);
                        $record->update(['password' => \Illuminate\Support\Facades\Hash::make($tempPass)]);
                        $token = $record->createToken('magic-link', ['*'], now()->addDays(365))->plainTextToken;
                        $pwaUrl = config('app.pwa_url', 'https://sertecapp.pendziuch.com');
                        $magicLink = $pwaUrl . '/l?t=' . $token;

                        $phone = preg_replace('/[^0-9]/', '', $record->phone);
                        if (!str_starts_with($phone, '54')) {
                            $phone = str_starts_with($phone, '11') ? '54' . $phone : '549' . $phone;
                        }
                        $msg = urlencode(
                            "Hola {$record->name}!\n\n" .
                            "Estos son tus datos de acceso a SerTecApp:\n\n" .
                            "Email: {$record->email}\n" .
                            "Contraseña: {$tempPass}\n\n" .
                            "Página para ingresar con usuario y contraseña:\n{$pwaUrl}\n\n" .
                            "O acceso directo sin escribir nada (un solo clic):\n{$magicLink}"
                        );
                        $whatsappUrl = "https://wa.me/{$phone}?text={$msg}";

                        \Filament\Notifications\Notification::make()
                            ->title('Datos generados para ' . $record->name)
                            ->success()->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('abrir_whatsapp')
                                    ->label('Abrir WhatsApp')->button()->color('success')
                                    ->url($whatsappUrl)->openUrlInNewTab(),
                            ])
                            ->send();
                    }),

                Tables\Actions\Action::make('enviar_acceso_email')
                    ->label('Enviar datos por email')->link()->color('info')
                    ->visible(fn (User $record) => !empty($record->email) && !static::isProtectedSuperAdmin($record)
                        && auth()->user()->hasAnyRole(['administrador', 'super_admin', 'supervisor']))
                    ->requiresConfirmation()
                    ->modalDescription('Se generará una contraseña temporal y se enviará por email junto con el magic link.')
                    ->action(function (User $record) {
                        $tempPass = \Illuminate\Support\Str::random(8);
                        $record->update(['password' => \Illuminate\Support\Facades\Hash::make($tempPass)]);
                        $token = $record->createToken('magic-link', ['*'], now()->addDays(365))->plainTextToken;
                        $accessUrl = config('app.pwa_url', 'https://sertecapp.pendziuch.com') . '/l?t=' . $token;
                        try {
                            \Illuminate\Support\Facades\Mail::to($record->email)
                                ->send(new \App\Mail\AccesoUsuarioMail($record, $accessUrl, $tempPass));
                            \Filament\Notifications\Notification::make()
                                ->title('Email enviado a ' . $record->email)->success()->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al enviar')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== 1 && !$record->hasAnyRole(['administrador', 'super_admin']))
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(fn ($records) => $records->filter(fn ($r) => $r->id !== 1 && !$r->hasAnyRole(['administrador', 'super_admin']))->each->delete()),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('roles');
        $user = auth()->user();
        if ($user?->hasRole('supervisor')) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['técnico', 'tecnico']));
        } elseif (!$user?->hasRole('super_admin')) {
            // administrador y demas roles ni siquiera ven las cuentas super_admin en el listado.
            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'));
        }
        return $query;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['administrador', 'super_admin', 'supervisor']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['administrador', 'super_admin', 'supervisor']);
    }

    public static function canEdit($record): bool
    {
        if (auth()->user()->hasRole('supervisor')) {
            return $record->hasAnyRole(['técnico', 'tecnico']);
        }
        // Un administrador no puede editar (ni resetear password/rol de) un super_admin.
        // Evita que se auto-otorgue el rol o manipule cuentas super_admin existentes.
        if (static::isProtectedSuperAdmin($record)) {
            return false;
        }
        return auth()->user()->hasAnyRole(['administrador', 'super_admin']);
    }

    /**
     * true si $record es super_admin y el usuario logueado no lo es -
     * en ese caso ninguna accion de edicion/reset de password debe estar disponible.
     */
    protected static function isProtectedSuperAdmin($record): bool
    {
        return $record->hasRole('super_admin') && !auth()->user()->hasRole('super_admin');
    }

    public static function canDelete($record): bool
    {
        if ($record->id === 1) return false;
        if ($record->hasAnyRole(['administrador', 'super_admin'])) return false;
        return auth()->user()->hasAnyRole(['administrador', 'super_admin', 'supervisor']);
    }
}
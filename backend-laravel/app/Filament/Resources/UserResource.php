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
                            if (auth()->check() && auth()->user()->hasRole('supervisor')) {
                                return Role::whereIn('name', ['técnico', 'tecnico'])->pluck('name', 'id');
                            }
                            return Role::pluck('name', 'id');
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
                    ->label('Nueva Clave')->icon('heroicon-o-key')->color('warning')
                    ->visible(fn () => auth()->user()->hasAnyRole(['administrador', 'super_admin']))
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $token = $record->createToken('magic-link', ['*'], now()->addDays(30))->plainTextToken;
                        $autoLoginUrl = config('app.pwa_url', 'https://sertecapp.pendziuch.com') . '/l?t=' . $token;
                        $phone = preg_replace('/[^0-9]/', '', $record->phone ?? '');
                        if (!str_starts_with($phone, '54')) {
                            $phone = str_starts_with($phone, '11') ? '54' . $phone : '549' . $phone;
                        }
                        $whatsappMessage = urlencode("Hola {$record->name}!\n\nTu acceso a la app:\n\n{$autoLoginUrl}\n\n(Guardá este link para acceder siempre)");
                        $whatsappUrl = "https://wa.me/{$phone}?text={$whatsappMessage}";
                        \Filament\Notifications\Notification::make()
                            ->title('Link generado')
                            ->body("Link: {$autoLoginUrl}")
                            ->success()->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('copy')
                                    ->label('Copiar Link')->button()->color('gray')
                                    ->extraAttributes(['x-on:click' => "navigator.clipboard.writeText('{$autoLoginUrl}'); \$tooltip('Copiado!', { timeout: 2000 })"]),
                                \Filament\Notifications\Actions\Action::make('whatsapp')
                                    ->label('WhatsApp')->button()->color('success')
                                    ->url($whatsappUrl)->openUrlInNewTab()
                                    ->visible(!empty($record->phone)),
                                \Filament\Notifications\Actions\Action::make('email_acceso')
                                    ->label('Enviar por Email')->button()->color('info')
                                    ->visible(!empty($record->email))
                                    ->action(function () use ($record, $autoLoginUrl) {
                                        try {
                                            \Illuminate\Support\Facades\Mail::to($record->email)
                                                ->send(new \App\Mail\AccesoUsuarioMail($record, $autoLoginUrl));
                                            \Filament\Notifications\Notification::make()
                                                ->title('Email enviado')->success()->send();
                                        } catch (\Exception $e) {
                                            \Filament\Notifications\Notification::make()
                                                ->title('Error')->body($e->getMessage())->danger()->send();
                                        }
                                    }),
                            ])->send();
                    }),

                Tables\Actions\Action::make('enviar_acceso_email')
                    ->label('Enviar Acceso por Email')->icon('heroicon-o-envelope')->color('info')
                    ->visible(fn (User $record) => !empty($record->email) && auth()->user()->hasAnyRole(['administrador', 'super_admin']))
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $token = $record->createToken('magic-link', ['*'], now()->addDays(30))->plainTextToken;
                        $accessUrl = config('app.pwa_url', 'https://sertecapp.pendziuch.com') . '/l?t=' . $token;
                        try {
                            \Illuminate\Support\Facades\Mail::to($record->email)
                                ->send(new \App\Mail\AccesoUsuarioMail($record, $accessUrl));
                            \Filament\Notifications\Notification::make()->title('Email enviado')->success()->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
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
        if (auth()->check() && auth()->user()->hasRole('supervisor')) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['técnico', 'tecnico']));
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
        return auth()->user()->hasAnyRole(['administrador', 'super_admin']);
    }

    public static function canDelete($record): bool
    {
        if ($record->id === 1) return false;
        if ($record->hasAnyRole(['administrador', 'super_admin'])) return false;
        return auth()->user()->hasAnyRole(['administrador', 'super_admin', 'supervisor']);
    }
}
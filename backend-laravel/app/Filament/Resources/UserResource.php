<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\ModuleManager;
use App\Services\UserService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
    protected static ?string $navigationGroup = 'Administracion';
    protected static ?int $navigationSort = 98;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informacion Personal')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')->email()->required()
                        ->unique(ignoreRecord: true)->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telefono')->tel()
                        ->placeholder('+54 11 1234-5678')->maxLength(20),
                    Forms\Components\TextInput::make('password')
                        ->label('Contrasena')->password()->revealable()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create')
                        ->default(fn () => \Illuminate\Support\Str::random(12)),
                ])->columns(2),

            Forms\Components\Section::make('Rol y Permisos')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Rol')->relationship('roles', 'name')
                        ->options(Role::pluck('name', 'id'))
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
                Tables\Columns\TextColumn::make('phone')->label('Telefono')->searchable()->toggleable()
                    ->formatStateUsing(fn ($state) => $state ?? '-'),
                Tables\Columns\TextColumn::make('roles.name')->label('Rol')->badge()
                    ->colors(['danger' => 'administrador', 'warning' => 'supervisor', 'success' => 'tecnico', 'info' => 'cliente'])
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
                    ->requiresConfirmation()
                    ->modalHeading('Generar Nuevo Acceso')
                    ->modalDescription('Se generara un link de acceso automatico y se mostraran opciones para enviarlo.')
                    ->action(function (User $record) {
                        $service    = app(UserService::class);
                        $accessUrl  = $service->generateMagicLink($record);
                        $whatsappUrl = !empty($record->phone)
                            ? $service->buildWhatsappAccessUrl($record, $accessUrl)
                            : null;

                        Notification::make()
                            ->title('Link generado')
                            ->body("Link: {$accessUrl}")
                            ->success()->persistent()
                            ->actions(array_filter([
                                \Filament\Notifications\Actions\Action::make('copy')
                                    ->label('Copiar Link')->button()->color('gray')
                                    ->extraAttributes([
                                        'x-on:click' => "navigator.clipboard.writeText('{$accessUrl}'); \$tooltip('Copiado!', { timeout: 2000 })"
                                    ]),
                                $whatsappUrl ? \Filament\Notifications\Actions\Action::make('whatsapp')
                                    ->label('Enviar WhatsApp')->button()->color('success')
                                    ->url($whatsappUrl)->openUrlInNewTab() : null,
                                !empty($record->email) ? \Filament\Notifications\Actions\Action::make('email_acceso')
                                    ->label('Enviar por Email')->button()->color('info')
                                    ->action(function () use ($record, $accessUrl) {
                                        try {
                                            app(UserService::class)->sendAccessEmail($record);
                                            Notification::make()->title('Email enviado a ' . $record->email)->success()->send();
                                        } catch (\Exception $e) {
                                            Notification::make()->title('Error al enviar email')->body($e->getMessage())->danger()->send();
                                        }
                                    }) : null,
                            ]))
                            ->send();
                    }),

                Tables\Actions\Action::make('enviar_acceso_email')
                    ->label('Enviar Acceso por Email')->icon('heroicon-o-envelope')->color('info')
                    ->visible(fn (User $record) => !empty($record->email))
                    ->requiresConfirmation()
                    ->modalHeading('Enviar acceso por email')
                    ->modalDescription(fn (User $record) => 'Se enviara un link de acceso a ' . $record->email)
                    ->action(function (User $record) {
                        try {
                            app(UserService::class)->sendAccessEmail($record);
                            Notification::make()->title('Email enviado a ' . $record->email)->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Error al enviar email')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')->icon('heroicon-o-chat-bubble-left-right')->color('success')
                    ->visible(fn (User $record) => !empty($record->phone))
                    ->url(function (User $record) {
                        $service    = app(UserService::class);
                        $accessUrl  = $service->generateMagicLink($record);
                        return $service->buildWhatsappAccessUrl($record, $accessUrl);
                    })
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record) => $record->id !== 1)->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(fn ($records) => $records->filter(fn ($r) => $r->id !== 1)->each->delete()),
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
        return parent::getEloquentQuery()->with('roles');
    }

    public static function canViewAny(): bool  { return auth()->user()->hasRole('administrador'); }
    public static function canCreate(): bool   { return auth()->user()->hasRole('administrador'); }
    public static function canEdit($record): bool   { return auth()->user()->hasRole('administrador'); }
    public static function canDelete($record): bool
    {
        return $record->id !== 1 && auth()->user()->hasRole('administrador');
    }
}
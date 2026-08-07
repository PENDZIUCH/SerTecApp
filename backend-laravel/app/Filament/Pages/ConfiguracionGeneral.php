<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ConfiguracionGeneral extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configuración General';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 80;
    protected static string $view = 'filament.pages.configuracion-general';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'app_name'        => SystemSetting::get('app_name', config('app.name', 'SerTecApp')),
            'app_description' => SystemSetting::get('app_description', 'Sistema de gestión de servicios técnicos'),
            'contact_phone'   => SystemSetting::get('contact_phone', ''),
            'contact_email'   => SystemSetting::get('contact_email', ''),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identidad del sistema')
                    ->schema([
                        Forms\Components\TextInput::make('app_name')
                            ->label('Nombre del sistema')
                            ->placeholder('SerTecApp')
                            ->required()
                            ->helperText('Aparece en emails, notificaciones y encabezado del sistema'),
                        Forms\Components\TextInput::make('app_description')
                            ->label('Descripción')
                            ->placeholder('Sistema de gestión de servicios técnicos'),
                    ])->columns(2),

                Forms\Components\Section::make('Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Teléfono de contacto')
                            ->placeholder('+54 11 1234-5678'),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email de contacto')
                            ->email()
                            ->placeholder('contacto@empresa.com'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            SystemSetting::set($key, $value);
        }

        // Actualizar en runtime sin tocar el .env (evita invalidar sesión CSRF)
        config(['app.name' => $data['app_name'] ?? 'SerTecApp']);

        Notification::make()
            ->title('Configuración guardada')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}

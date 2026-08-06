<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Mail;

class ConfiguracionEmail extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Configuración Email';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.pages.configuracion-email';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mail_host'         => SystemSetting::get('mail_host', ''),
            'mail_port'         => SystemSetting::get('mail_port', '465'),
            'mail_username'     => SystemSetting::get('mail_username', ''),
            'mail_password'     => SystemSetting::get('mail_password', ''),
            'mail_encryption'   => SystemSetting::get('mail_encryption', 'ssl'),
            'mail_from_address' => SystemSetting::get('mail_from_address', ''),
            'mail_from_name'    => SystemSetting::get('mail_from_name', 'SerTecApp'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Prueba de envío')
                    ->schema([
                        Forms\Components\TextInput::make('test_email')
                            ->label('Enviar prueba a este email')
                            ->email()
                            ->placeholder('tu@email.com')
                            ->helperText('Dejalo vacío para enviarlo al usuario SMTP configurado'),
                    ]),

                Forms\Components\Section::make('Servidor SMTP')
                    ->schema([
                        Forms\Components\TextInput::make('mail_host')
                            ->label('Host SMTP')
                            ->placeholder('smtp.hostinger.com')
                            ->required(),
                        Forms\Components\TextInput::make('mail_port')
                            ->label('Puerto')
                            ->placeholder('465')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('mail_encryption')
                            ->label('Cifrado')
                            ->options(['ssl' => 'SSL', 'tls' => 'TLS', '' => 'Ninguno'])
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Credenciales')
                    ->schema([
                        Forms\Components\TextInput::make('mail_username')
                            ->label('Usuario (email)')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Remitente')
                    ->schema([
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('Email remitente')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('Nombre remitente')
                            ->required(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            if ($key !== 'test_email') {
                SystemSetting::set($key, $value);
            }
        }

        // Escribir al .env para que Laravel lo lea nativamente
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $map = [
            'MAIL_MAILER'       => 'smtp',
            'MAIL_HOST'         => $data['mail_host'] ?? '',
            'MAIL_PORT'         => $data['mail_port'] ?? '465',
            'MAIL_USERNAME'     => $data['mail_username'] ?? '',
            'MAIL_PASSWORD'     => $data['mail_password'] ?? '',
            'MAIL_ENCRYPTION'   => $data['mail_encryption'] ?? 'ssl',
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? '',
            'MAIL_FROM_NAME'    => '"' . ($data['mail_from_name'] ?? 'SerTecApp') . '"',
        ];

        foreach ($map as $envKey => $envValue) {
            if (preg_match("/^{$envKey}=/m", $envContent)) {
                $envContent = preg_replace("/^{$envKey}=.*/m", "{$envKey}={$envValue}", $envContent);
            } else {
                $envContent .= "\n{$envKey}={$envValue}";
            }
        }

        file_put_contents($envPath, $envContent);

        Notification::make()
            ->title('Configuración guardada')
            ->body('Recargá la página antes de hacer la prueba.')
            ->success()
            ->send();
    }

    public function testEmail(): void
    {
        $data = $this->form->getState();

        config([
            'mail.mailer'                  => 'smtp',
            'mail.mailers.smtp.host'       => $data['mail_host'],
            'mail.mailers.smtp.port'       => (int) $data['mail_port'],
            'mail.mailers.smtp.username'   => $data['mail_username'],
            'mail.mailers.smtp.password'   => $data['mail_password'],
            'mail.mailers.smtp.encryption' => $data['mail_encryption'] ?: null,
            'mail.from.address'            => $data['mail_from_address'],
            'mail.from.name'               => $data['mail_from_name'],
        ]);

        // Limpiar mailer cacheado para forzar nueva conexión (Laravel 11/Symfony Mailer)
        app()->forgetInstance('mailer');
        app()->forgetInstance('mail.manager');
        Mail::forgetMailers();

        try {
            $destino = !empty($data['test_email']) ? $data['test_email'] : $data['mail_username'];
            Mail::raw(
                'Email de prueba desde SerTecApp. Si recibís este mensaje la configuración SMTP es correcta.',
                function ($message) use ($data, $destino) {
                    $message->to($destino)
                            ->subject('✅ Prueba de email — SerTecApp — ' . now()->format('H:i:s'));
                }
            );

            Notification::make()
                ->title('✅ Email enviado correctamente')
                ->body('Revisá tu bandeja de entrada y spam en: ' . $destino)
                ->success()
                ->persistent()
                ->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
            Notification::make()
                ->title('❌ Error de conexión SMTP')
                ->body('No se pudo conectar al servidor. Verificá host, puerto y cifrado. Detalle: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('❌ Error al enviar')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('test')
                ->label('Enviar email de prueba')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action('testEmail'),
            \Filament\Actions\Action::make('save')
                ->label('Guardar configuración')
                ->icon('heroicon-o-check')
                ->action('save'),
        ];
    }
}

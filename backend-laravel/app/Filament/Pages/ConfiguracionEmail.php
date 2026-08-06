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
            'mail_mailer'       => SystemSetting::get('mail_mailer', 'smtp'),
            'mail_host'         => SystemSetting::get('mail_host', ''),
            'mail_port'         => SystemSetting::get('mail_port', '465'),
            'mail_username'     => SystemSetting::get('mail_username', ''),
            'mail_password'     => SystemSetting::get('mail_password', ''),
            'mail_encryption'   => SystemSetting::get('mail_encryption', 'ssl'),
            'mail_from_address' => SystemSetting::get('mail_from_address', ''),
            'mail_from_name'    => SystemSetting::get('mail_from_name', 'SerTecApp'),
            'test_email'        => '',
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

                Forms\Components\Section::make('Driver de correo')
                    ->schema([
                        Forms\Components\Select::make('mail_mailer')
                            ->label('Método de envío')
                            ->options([
                                'smtp'     => 'SMTP (configuración propia)',
                                'sendmail' => 'Sendmail (servidor de Hostinger)',
                            ])
                            ->default('smtp')
                            ->live()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Servidor SMTP')
                    ->schema([
                        Forms\Components\TextInput::make('mail_host')
                            ->label('Host SMTP')
                            ->placeholder('smtp.hostinger.com'),
                        Forms\Components\TextInput::make('mail_port')
                            ->label('Puerto')
                            ->placeholder('465')
                            ->numeric(),
                        Forms\Components\Select::make('mail_encryption')
                            ->label('Cifrado')
                            ->options(['ssl' => 'SSL', 'tls' => 'TLS', '' => 'Ninguno']),
                    ])
                    ->columns(3)
                    ->visible(fn (Forms\Get $get) => $get('mail_mailer') === 'smtp'),

                Forms\Components\Section::make('Credenciales')
                    ->schema([
                        Forms\Components\TextInput::make('mail_username')
                            ->label('Usuario (email)')
                            ->email(),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable(),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('mail_mailer') === 'smtp'),

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

        $mailer = $data['mail_mailer'] ?? 'smtp';

        $map = [
            'MAIL_MAILER'       => $mailer,
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? '',
            'MAIL_FROM_NAME'    => '"' . ($data['mail_from_name'] ?? 'SerTecApp') . '"',
        ];

        if ($mailer === 'smtp') {
            $map['MAIL_HOST']       = $data['mail_host'] ?? '';
            $map['MAIL_PORT']       = $data['mail_port'] ?? '465';
            $map['MAIL_USERNAME']   = $data['mail_username'] ?? '';
            $map['MAIL_PASSWORD']   = $data['mail_password'] ?? '';
            $map['MAIL_ENCRYPTION'] = $data['mail_encryption'] ?? 'ssl';
        }

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
        $destino = !empty($data['test_email']) ? $data['test_email'] : ($data['mail_username'] ?? $data['mail_from_address']);

        if (empty($destino)) {
            Notification::make()
                ->title('Ingresá un email de destino')
                ->danger()
                ->send();
            return;
        }

        try {
            Mail::raw(
                'Email de prueba desde SerTecApp. Si recibís este mensaje la configuración es correcta.',
                function ($message) use ($destino) {
                    $message->to($destino)
                            ->subject('✅ Prueba de email — SerTecApp — ' . now()->format('H:i:s'));
                }
            );

            Notification::make()
                ->title('✅ Email enviado')
                ->body('Revisá tu bandeja en: ' . $destino)
                ->success()
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

@component('mail::message')
# Hola {{ $user->name }}!

Te enviamos tu acceso a la aplicación de Fitness Company.

@if($tempPass)
Si preferís ingresar manualmente en vez de usar el link, tus datos son:

- **Email:** {{ $user->email }}
- **Contraseña:** {{ $tempPass }}
@endif

@component('mail::button', ['url' => $accessUrl, 'color' => 'primary'])
Acceder a la app
@endcomponent

También podés copiar este link directamente:  
{{ $accessUrl }}

Este link te permite ingresar automáticamente sin necesidad de contraseña.

Saludos,  
{{ config('app.name') }}
@endcomponent

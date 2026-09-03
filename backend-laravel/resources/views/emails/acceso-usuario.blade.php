@component('mail::message')
# Hola {{ $user->name }}!

Te enviamos tu acceso a la aplicación de Fitness Company.

@component('mail::button', ['url' => $accessUrl, 'color' => 'primary'])
Acceder a la app
@endcomponent

También podés copiar este link directamente:  
{{ $accessUrl }}

Este link te permite ingresar automáticamente sin necesidad de contraseña.

Saludos,  
{{ config('app.name') }}
@endcomponent

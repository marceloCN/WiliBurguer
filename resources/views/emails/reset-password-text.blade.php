{{-- Plantilla de correo: recuperación de contraseña (texto plano) --}}
{{ strtoupper($sitioNombre ?? 'BURGER WILLY') }}{{ !empty($sitioEslogan) ? ' — ' . $sitioEslogan : '' }}
========================================================================

{{ $greeting ?? '' }}


@if (!empty($introLines))
@foreach ($introLines as $line)
{{ $line }}
@endforeach
@endif

@if (!empty($actionText) && !empty($actionUrl))
{{ $actionText }}: {{ $actionUrl }}
@endif

@if (!empty($outroLines))
@foreach ($outroLines as $line)
{{ $line }}
@endforeach
@endif

========================================================================
{{ $sitioDireccion ?? '' }}{{ !empty($sitioDireccion) && (!empty($sitioTelefono) || !empty($sitioEmail)) ? ' — ' : '' }}{{ $sitioTelefono ?? '' }}{{ !empty($sitioTelefono) && !empty($sitioEmail) ? ' — ' : '' }}{{ $sitioEmail ?? '' }}
© {{ date('Y') }} {{ $sitioNombre ?? 'Burger Willy' }}
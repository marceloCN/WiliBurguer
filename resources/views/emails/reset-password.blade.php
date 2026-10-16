{{-- Plantilla de correo: recuperación de contraseña (HTML) --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $sitioNombre ?? 'Burger Willy' }} · Recuperación de contraseña</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="margin:0;padding:24px 12px;background-color:#f4f5f7;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                    style="width:100%;max-width:600px;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">

                    {{-- Cabecera --}}
                    <tr>
                        <td style="background-color:#374151;padding:28px 32px;text-align:center;">
                            <div style="font-size:24px;font-weight:bold;color:#ffffff;letter-spacing:.5px;">
                                {{ $sitioNombre ?? '🍔 Burger Willy and Grill' }}
                            </div>
                            @if (!empty($sitioEslogan))
                                <div style="margin-top:4px;font-size:13px;color:#cbd5e1;">
                                    {{ $sitioEslogan }}
                                </div>
                            @endif
                        </td>
                    </tr>

                    {{-- Cuerpo --}}
                    <tr>
                        <td style="padding:32px 36px;">
                            @if (!empty($greeting))
                                <h1 style="margin:0 0 16px;font-size:20px;font-weight:bold;color:#1f2937;">
                                    {{ $greeting }}
                                </h1>
                            @endif

                            @if (!empty($introLines))
                                @foreach ($introLines as $line)
                                    <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#374151;">
                                        {{ $line }}
                                    </p>
                                @endforeach
                            @endif

                            @if (!empty($actionText) && !empty($actionUrl))
                                <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                    style="margin:26px 0;">
                                    <tr>
                                        <td>
                                            <a href="{{ $actionUrl }}"
                                                style="display:inline-block;background-color:#10b981;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;padding:13px 30px;border-radius:8px;">
                                                {{ $actionText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="margin:0 0 14px;font-size:13px;color:#6b7280;">
                                    Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                    <a href="{{ $actionUrl }}" style="color:#4b5563;word-break:break-all;">
                                        {{ $actionUrl }}
                                    </a>
                                </p>
                            @endif

                            @if (!empty($outroLines))
                                @foreach ($outroLines as $line)
                                    <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#374151;">
                                        {{ $line }}
                                    </p>
                                @endforeach
                            @endif

                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:22px 0;">

                            <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.5;">
                                Si no utilizaste la opción "Olvidé mi contraseña", ignora este correo.
                            </p>
                        </td>
                    </tr>

                    {{-- Pie --}}
                    <tr>
                        <td style="background-color:#f9fafb;padding:20px 32px;text-align:center;font-size:12px;color:#6b7280;line-height:1.7;">
                            @if (!empty($sitioDireccion))
                                <span>{{ $sitioDireccion }}</span><br>
                            @endif
                            @if (!empty($sitioTelefono))
                                <span>Tel. {{ $sitioTelefono }}</span>
                                @if (!empty($sitioEmail))
                                    <span> &nbsp;·&nbsp; </span>
                                @endif
                            @endif
                            @if (!empty($sitioEmail))
                                <a href="mailto:{{ $sitioEmail }}" style="color:#4b5563;text-decoration:none;">{{ $sitioEmail }}</a>
                            @endif
                            <br>
                            © {{ date('Y') }} {{ $sitioNombre ?? 'Burger Willy' }}
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Cerrado</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; padding: 32px 40px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .header p { margin: 6px 0 0; opacity: 0.85; font-size: 14px; }
        .body { padding: 36px 40px; color: #2d3748; }
        .ticket-box { background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 24px 0; }
        .ticket-box .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #718096; margin-bottom: 4px; }
        .ticket-box .value { font-size: 18px; font-weight: 700; color: #1a202c; }
        .ticket-box .sub { font-size: 14px; color: #4a5568; margin-top: 6px; }
        .badge-closed { display: inline-block; background: #2d3748; color: white; font-size: 12px; padding: 3px 10px; border-radius: 20px; margin-top: 10px; }
        .info-box { border-left: 4px solid #e67e22; background: #fef9f3; border-radius: 6px; padding: 16px 20px; margin: 24px 0; }
        .info-box p { margin: 0; font-size: 14px; color: #744210; }
        .btn { display: inline-block; margin-top: 24px; padding: 14px 28px; background: linear-gradient(135deg, #3498db, #2980b9); color: white; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
        .footer { background: #f8f9fa; padding: 20px 40px; text-align: center; font-size: 12px; color: #a0aec0; border-top: 1px solid #e2e8f0; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚫ Ticket Cerrado Automáticamente</h1>
            <p>No recibimos tu respuesta dentro del plazo establecido</p>
        </div>

        <div class="body">
            <p>Hola <strong>{{ $ticket->guest_name }}</strong>,</p>

            <p>Te escribimos para informarte que tu ticket de soporte ha sido <strong>cerrado automáticamente</strong> porque no recibimos tu respuesta dentro del plazo de tiempo establecido.</p>

            <div class="ticket-box">
                <div class="label">Número de ticket</div>
                <div class="value">{{ $ticket->ticket_number }}</div>
                <div class="sub">{{ $ticket->title }}</div>
                <div class="sub" style="margin-top: 8px;">
                    <strong>Categoría:</strong> {{ ucfirst($ticket->category ?? 'N/A') }}
                    &nbsp;•&nbsp;
                    <strong>Departamento:</strong> {{ $ticket->department->name ?? 'N/A' }}
                </div>
                <span class="badge-closed">⚫ Cerrado</span>
            </div>

            <div class="info-box">
                <p>
                    ⚠️ <strong>¿El problema sigue ocurriendo?</strong><br>
                    Si aún tienes el mismo problema, por favor abre un <strong>nuevo ticket</strong> para que nuestro equipo de soporte pueda atenderte.
                </p>
            </div>

            <hr class="divider">

            <p style="font-size: 14px; color: #718096;">
                Este cierre es automático y se produce cuando no se recibe respuesta del usuario dentro del plazo SLA.
                Si crees que esto fue un error, contáctanos directamente por correo o abre un nuevo ticket indicando el número anterior: <strong>{{ $ticket->ticket_number }}</strong>.
            </p>
        </div>

        <div class="footer">
            <p>Este correo fue enviado automáticamente por <strong>Conecta Soporte</strong>. Por favor no respondas a este correo.</p>
            <p>Si tienes preguntas, contacta a tu equipo de soporte.</p>
        </div>
    </div>
</body>
</html>

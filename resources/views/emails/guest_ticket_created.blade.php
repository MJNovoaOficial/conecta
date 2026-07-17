<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Creado — Conecta Soporte</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #1a2332 0%, #3498db 100%); color: white; padding: 32px 40px; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
        .header p { margin: 6px 0 0; opacity: 0.85; font-size: 14px; }
        .body { padding: 36px 40px; color: #2d3748; line-height: 1.6; }
        .ticket-box { background: #f0f7ff; border: 1.5px solid #bfdbfe; border-radius: 8px; padding: 20px; margin: 24px 0; }
        .ticket-box .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #718096; margin-bottom: 4px; }
        .ticket-box .value { font-size: 22px; font-weight: 700; color: #1e3a5f; font-family: monospace; }
        .ticket-box .sub { font-size: 14px; color: #4a5568; margin-top: 8px; }
        .info-row { display: flex; gap: 16px; margin-top: 14px; flex-wrap: wrap; }
        .info-item { flex: 1; min-width: 120px; }
        .info-item .lbl { font-size: 11px; color: #a0aec0; text-transform: uppercase; letter-spacing: .06em; }
        .info-item .val { font-size: 13px; font-weight: 600; color: #2d3748; margin-top: 2px; }
        .alert-box { border-left: 4px solid #f59e0b; background: #fffbeb; border-radius: 6px; padding: 14px 18px; margin: 20px 0; font-size: 14px; color: #92400e; }
        .btn { display: inline-block; margin-top: 24px; padding: 14px 28px; background: linear-gradient(135deg, #3498db, #2980b9); color: white; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
        .footer { background: #f8f9fa; padding: 20px 40px; text-align: center; font-size: 12px; color: #a0aec0; border-top: 1px solid #e2e8f0; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
        .badge-open { display: inline-block; background: #d1fae5; color: #065f46; font-size: 11px; padding: 2px 10px; border-radius: 20px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Ticket Registrado Exitosamente</h1>
            <p>Tu solicitud ha sido recibida y será atendida a la brevedad</p>
        </div>

        <div class="body">
            <p>Hola <strong>{{ $ticket->guest_name }}</strong>,</p>

            <p>Te confirmamos que hemos recibido tu solicitud de soporte. A continuación encontrarás los detalles de tu ticket:</p>

            <div class="ticket-box">
                <div class="label">Número de Ticket</div>
                <div class="value">{{ $ticket->ticket_number }}</div>
                <div class="sub"><strong>{{ $ticket->title }}</strong></div>
                <div class="info-row">
                    <div class="info-item">
                        <div class="lbl">Estado</div>
                        <div class="val"><span class="badge-open">Abierto</span></div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Prioridad</div>
                        <div class="val">{{ ucfirst($ticket->priority) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Creado</div>
                        <div class="val">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <div class="alert-box">
                <strong>⏱ Importante:</strong> Si el equipo de soporte te solicita información adicional,
                tendrás <strong>2 horas</strong> para responder a través del enlace de seguimiento.
                Si no respondes en ese plazo, el ticket se cerrará automáticamente.
            </div>

            <p>Puedes consultar el estado de tu ticket en cualquier momento usando el siguiente enlace:</p>

            <a href="{{ $trackingUrl }}" class="btn">
                🔍 Ver estado de mi ticket
            </a>

            <hr class="divider">

            <p style="font-size:13px;color:#718096;">
                Guarda este correo para tener acceso a tu ticket. Si el problema persiste después de la resolución,
                puedes abrir un nuevo ticket en <a href="{{ config('app.url') }}" style="color:#3498db;">{{ config('app.url') }}</a>.
            </p>
        </div>

        <div class="footer">
            <strong>Conecta — Mesa de Ayuda</strong><br>
            Este es un correo automático, por favor no respondas a este mensaje.<br>
            Para contactar con soporte usa el enlace de seguimiento de tu ticket.
        </div>
    </div>
</body>
</html>

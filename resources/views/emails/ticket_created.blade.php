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
        .sla-box { border-left: 4px solid #6366f1; background: #f5f3ff; border-radius: 6px; padding: 14px 18px; margin: 20px 0; font-size: 14px; color: #4c1d95; }
        .btn { display: inline-block; margin-top: 24px; padding: 14px 28px; background: linear-gradient(135deg, #3498db, #2980b9); color: white; text-decoration: none; border-radius: 8px; font-size: 15px; font-weight: 600; }
        .footer { background: #f8f9fa; padding: 20px 40px; text-align: center; font-size: 12px; color: #a0aec0; border-top: 1px solid #e2e8f0; }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
        .badge-open { display: inline-block; background: #d1fae5; color: #065f46; font-size: 11px; padding: 2px 10px; border-radius: 20px; font-weight: 600; }
        .badge-low      { background:#f0fdf4; color:#166534; }
        .badge-medium   { background:#fefce8; color:#854d0e; }
        .badge-high     { background:#fff7ed; color:#9a3412; }
        .badge-critical { background:#fef2f2; color:#991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Ticket Registrado Exitosamente</h1>
            <p>Tu solicitud ha sido recibida y será atendida a la brevedad</p>
        </div>

        <div class="body">
            <p>Hola <strong>{{ $user->name }}</strong>,</p>

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
                        <div class="val">
                            @php
                                $priLabel = ['low'=>'Baja','medium'=>'Media','high'=>'Alta','critical'=>'Crítica'];
                                $priClass = ['low'=>'badge-low','medium'=>'badge-medium','high'=>'badge-high','critical'=>'badge-critical'];
                            @endphp
                            <span class="{{ $priClass[$ticket->priority] ?? '' }}" style="display:inline-block;font-size:11px;padding:2px 10px;border-radius:20px;font-weight:600;">
                                {{ $priLabel[$ticket->priority] ?? ucfirst($ticket->priority) }}
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Creado</div>
                        <div class="val">{{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            @if($ticket->sla_resolution_deadline_at)
            <div class="sla-box">
                <strong>⏱ Plazo de resolución:</strong>
                {{ $ticket->sla_resolution_deadline_at->format('d/m/Y H:i') }}
                ({{ $ticket->sla_resolution_deadline_at->diffForHumans() }})
            </div>
            @endif

            <p>Puedes consultar el estado de tu ticket, responder a comentarios del equipo de soporte y ver el historial completo en el siguiente enlace:</p>

            <a href="{{ $ticketUrl }}" class="btn">
                🔍 Ver estado de mi ticket
            </a>

            <hr class="divider">

            <p style="font-size:13px;color:#718096;">
                Guarda el número de ticket <strong>{{ $ticket->ticket_number }}</strong> para futuras consultas.
                Si el problema persiste después de la resolución, puedes abrir un nuevo ticket en
                <a href="{{ config('app.url') }}" style="color:#3498db;">{{ config('app.url') }}</a>.
            </p>
        </div>

        <div class="footer">
            <strong>Conecta — Mesa de Ayuda</strong><br>
            Este es un correo automático, por favor no respondas a este mensaje.<br>
            Para contactar con soporte ingresa a través del enlace de seguimiento.
        </div>
    </div>
</body>
</html>

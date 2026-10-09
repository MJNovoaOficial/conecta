<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Actualización de ticket</title></head>
<body style="margin:0;padding:20px;background:#f4f6f9;font-family:Arial,sans-serif;color:#2d3748;">
    <div style="max-width:600px;margin:auto;padding:24px;background:white;border-radius:10px;">
        <h1 style="font-size:22px;">Actualización de tu solicitud</h1>
        <p>Hola {{ $ticket->guest_name }},</p>
        <p>{{ $statusMessage }}</p>
        <p><strong>{{ $ticket->ticket_number }}</strong> — {{ $ticket->title }}</p>
        <p><strong>Solución registrada:</strong></p>
        <div style="white-space:pre-wrap;overflow-wrap:anywhere;">{{ $ticket->solution_text }}</div>
        <p><a href="{{ route('tickets.guest.show', $ticket->guest_token) }}" style="display:inline-block;padding:12px 18px;border-radius:6px;background:#2563eb;color:white;text-decoration:none;">Ver mi ticket</a></p>
        <p style="font-size:12px;color:#718096;">Conecta Soporte. Para responder, usa el enlace de tu ticket.</p>
    </div>
</body>
</html>

@extends("layouts.app")
@section("title", "Notificaciones - Conecta")

@section("content")
<div style="min-height:calc(100vh - 52px);background:#f0f4f8;padding:32px 24px;">
<div style="max-width:760px;margin:0 auto;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:28px;">
        <div>
            <h1 style="margin:0 0 4px;font-size:1.45rem;font-weight:800;color:#1a2332;display:flex;align-items:center;gap:10px;">
                <span style="width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#4f8cff,#2563eb);display:inline-flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(79,140,255,0.35);">
                    <i class="fas fa-bell" style="color:#fff;font-size:1rem;"></i>
                </span>
                Notificaciones
            </h1>
            <p style="margin:0;color:#718096;font-size:0.84rem;padding-left:50px;">
                Historial de todas tus notificaciones
            </p>
        </div>
        @if(!$notificaciones->isEmpty() && $unreadCount > 0)
        <form method="POST" action="{{ route("notifications.readAll") }}">
            @csrf
            <button type="submit" style="display:inline-flex;align-items:center;gap:7px;padding:8px 16px;background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.82rem;font-weight:600;color:#4a5568;cursor:pointer;transition:all 0.2s;box-shadow:0 1px 4px rgba(0,0,0,0.06);"
                    onmouseenter="this.style.borderColor='#4f8cff';this.style.color='#4f8cff';"
                    onmouseleave="this.style.borderColor='#e2e8f0';this.style.color='#4a5568';">
                <i class="fas fa-check-double"></i> Marcar todas como leídas
            </button>
        </form>
        @endif
    </div>

    {{-- Badge count --}}
    @if(!$notificaciones->isEmpty())
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
        @if($unreadCount > 0)
            <span style="display:inline-flex;align-items:center;gap:6px;background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:999px;font-size:0.78rem;font-weight:700;">
                <span style="width:7px;height:7px;background:#3b82f6;border-radius:50%;display:inline-block;animation:pulse 1.8s ease-in-out infinite;"></span>
                {{ $unreadCount }} sin leer
            </span>
        @else
            <span style="display:inline-flex;align-items:center;gap:6px;background:#dcfce7;color:#166534;padding:4px 12px;border-radius:999px;font-size:0.78rem;font-weight:700;">
                <i class="fas fa-check-circle" style="font-size:0.75rem;"></i> Todo leído
            </span>
        @endif
        <span style="color:#a0aec0;font-size:0.78rem;">{{ $notificaciones->count() }} notificaciones en total</span>
    </div>
    @endif

    {{-- Empty state --}}
    @if($notificaciones->isEmpty())
    <div style="background:#fff;border-radius:16px;border:1px solid #e8ecf0;box-shadow:0 2px 12px rgba(0,0,0,0.05);padding:60px 24px;text-align:center;">
        <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#f0f4f8,#e2e8f0);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <i class="fas fa-bell-slash" style="font-size:1.8rem;color:#a0aec0;"></i>
        </div>
        <h3 style="margin:0 0 8px;font-size:1.05rem;font-weight:700;color:#1a2332;">Todo tranquilo por aquí</h3>
        <p style="margin:0;color:#718096;font-size:0.87rem;">No tienes notificaciones pendientes.</p>
    </div>

    {{-- Notification list --}}
    @else
    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($notificaciones as $notif)
        @php
            $isRead = $notif->isRead();
            $typeConfig = [
                "new_ticket" => ["icon" => "fa-ticket-alt",   "bg" => "#dbeafe", "color" => "#1e40af", "emoji" => "🎫"],
                "assigned"   => ["icon" => "fa-user-check",   "bg" => "#fef3c7", "color" => "#92400e", "emoji" => "👤"],
                "comment"    => ["icon" => "fa-comment-dots", "bg" => "#f0fdf4", "color" => "#166534", "emoji" => "💬"],
                "forwarded"  => ["icon" => "fa-share",        "bg" => "#fdf4ff", "color" => "#6b21a8", "emoji" => "↗️"],
                "closed"     => ["icon" => "fa-lock",         "bg" => "#f1f5f9", "color" => "#475569", "emoji" => "🔒"],
            ];
            $cfg = $typeConfig[$notif->type] ?? ["icon" => "fa-bell", "bg" => "#f0f4f8", "color" => "#4a5568", "emoji" => "🔔"];
        @endphp
        <div style="background:#fff;border-radius:14px;border:1px solid {{ $isRead ? "#e8ecf0" : "#bfdbfe" }};box-shadow:{{ $isRead ? "0 1px 4px rgba(0,0,0,0.04)" : "0 2px 12px rgba(79,140,255,0.12)" }};padding:16px 20px;display:flex;align-items:flex-start;gap:14px;transition:box-shadow 0.2s,transform 0.15s;position:relative;overflow:hidden;"
             onmouseenter="this.style.boxShadow='0 4px 20px rgba(0,0,0,0.10)';this.style.transform='translateY(-1px)';"
             onmouseleave="this.style.boxShadow='{{ $isRead ? "0 1px 4px rgba(0,0,0,0.04)" : "0 2px 12px rgba(79,140,255,0.12)" }}';this.style.transform='translateY(0)';">

            {{-- Indicador no leído --}}
            @if(!$isRead)
            <div style="position:absolute;left:0;top:0;bottom:0;width:4px;background:linear-gradient(180deg,#4f8cff,#2563eb);border-radius:14px 0 0 14px;"></div>
            @endif

            {{-- Icono del tipo --}}
            <div style="width:42px;height:42px;border-radius:10px;background:{{ $cfg["bg"] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas {{ $cfg["icon"] }}" style="color:{{ $cfg["color"] }};font-size:1rem;"></i>
            </div>

            {{-- Contenido --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <div style="font-weight:{{ $isRead ? 500 : 700 }};font-size:0.88rem;color:#1a2332;line-height:1.4;">
                        {{ $notif->title }}
                        @if(!$isRead)
                            <span style="display:inline-block;margin-left:6px;padding:1px 7px;background:#dbeafe;color:#1e40af;border-radius:999px;font-size:0.68rem;font-weight:700;vertical-align:middle;">NUEVO</span>
                        @endif
                    </div>
                    <span style="font-size:0.73rem;color:#a0aec0;white-space:nowrap;flex-shrink:0;">
                        <i class="fas fa-clock" style="margin-right:3px;"></i>{{ $notif->created_at->diffForHumans() }}
                    </span>
                </div>
                @if($notif->body)
                <div style="font-size:0.81rem;color:#718096;margin-top:4px;line-height:1.5;">{{ $notif->body }}</div>
                @endif
                @if($notif->ticket_id)
                <div style="margin-top:10px;">
                    <form method="POST" action="{{ route("notifications.read", $notif) }}" style="display:inline;">
                        @csrf
                        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:linear-gradient(135deg,#4f8cff,#2563eb);color:#fff;border:none;border-radius:7px;font-size:0.78rem;font-weight:600;cursor:pointer;transition:opacity 0.2s;box-shadow:0 2px 8px rgba(79,140,255,0.3);"
                                onmouseenter="this.style.opacity=".85"" onmouseleave="this.style.opacity="1"">
                            <i class="fas fa-external-link-alt" style="font-size:0.72rem;"></i> Ver ticket
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
</div>
@endsection

@section("styles")
<style>
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.5; transform: scale(1.3); }
}
</style>
@endsection

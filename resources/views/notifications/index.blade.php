@extends('layouts.app')
@section('title', 'Notificaciones')

@section('content')
<div class="page-header">
    <h1 class="page-title">Notificaciones</h1>
    <p class="page-subtitle">Historial de todas tus notificaciones</p>
</div>

<div style="max-width:700px;">
    @if($notificaciones->isEmpty())
        <div class="card">
            <div class="card-body empty-state">
                <i class="bi bi-bell-slash" style="font-size:2rem;color:var(--text-muted);display:block;margin-bottom:.75rem;"></i>
                <p>No tienes notificaciones.</p>
            </div>
        </div>
    @else
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <span style="font-size:.85rem;color:var(--text-muted);">{{ $unreadCount }} sin leer</span>
            @if($unreadCount > 0)
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button class="btn btn-sm btn-outline" type="submit">Marcar todas como leídas</button>
            </form>
            @endif
        </div>
        @foreach($notificaciones as $notif)
        <div class="card" style="margin-bottom:.5rem;border-left:3px solid {{ $notif->isRead() ? 'var(--border-color)' : 'var(--accent)' }};">
            <div class="card-body" style="padding:.75rem 1rem;display:flex;align-items:flex-start;gap:.75rem;">
                <span style="font-size:1.2rem;">
                    @switch($notif->type)
                        @case('new_ticket') 🎫 @break
                        @case('assigned')   👤 @break
                        @case('comment')    💬 @break
                        @case('forwarded')  ↗️ @break
                        @case('closed')     🔒 @break
                        @default            🔔
                    @endswitch
                </span>
                <div style="flex:1;">
                    <div style="font-weight:{{ $notif->isRead() ? 500 : 700 }};font-size:.88rem;color:var(--text-primary);">
                        {{ $notif->title }}
                    </div>
                    @if($notif->body)
                    <div style="font-size:.8rem;color:var(--text-muted);margin-top:2px;">{{ $notif->body }}</div>
                    @endif
                    <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                </div>
                <div style="display:flex;gap:.4rem;align-items:center;">
                    @if($notif->ticket_id)
                    <a href="{{ route('notifications.read', $notif) }}" class="btn btn-sm btn-outline" style="font-size:.78rem;padding:.2rem .5rem;">
                        Ver ticket
                    </a>
                    @endif
                    @if(!$notif->isRead())
                    <span style="width:8px;height:8px;background:var(--accent);border-radius:50%;display:inline-block;flex-shrink:0;"></span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    @endif
</div>
@endsection

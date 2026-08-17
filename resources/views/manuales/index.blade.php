@extends('layouts.app')
@section('title', 'Manuales de Ayuda — Conecta')

@section('content')
<div class="page-wrapper">
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-book me-2"></i>Manuales</span>
            </div>
            <a href="{{ route('manuales.index') }}" class="sidebar-item active">
                <div class="item-left"><span class="item-icon"><i class="fas fa-file-pdf"></i></span>Todos los Manuales</div>
                <span class="sidebar-badge">{{ $manuales->count() }}</span>
            </a>
            @foreach($categorias as $cat)
            <a href="{{ route('manuales.index', ['categoria' => $cat]) }}"
               class="sidebar-item {{ request('categoria') === $cat ? 'active' : '' }}">
                <div class="item-left"><span class="item-icon"><i class="fas fa-tag"></i></span>{{ ucfirst($cat) }}</div>
            </a>
            @endforeach
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-header"><span><i class="fas fa-link me-2"></i>Accesos</span></div>
            <a href="{{ route('tickets.index') }}" class="sidebar-item">
                <div class="item-left"><span class="item-icon"><i class="fas fa-ticket-alt"></i></span>Mis Tickets</div>
            </a>
            <a href="{{ route('ayuda.index') }}" class="sidebar-item">
                <div class="item-left"><span class="item-icon"><i class="fas fa-question-circle"></i></span>Centro de Ayuda</div>
            </a>
        </div>
    </aside>

    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-file-pdf" style="color:#ef4444;margin-right:8px;"></i>Manuales Descargables</h1>
            <div class="breadcrumb-bar">
                <a href="{{ route('home') }}">Inicio</a>
                <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                <span>Manuales</span>
            </div>
        </div>

        {{-- Buscador --}}
        <form method="GET" action="{{ route('manuales.index') }}"
              style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
            @if(request('categoria'))
                <input type="hidden" name="categoria" value="{{ request('categoria') }}">
            @endif
            <div style="flex:1;min-width:240px;position:relative;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#a0aec0;"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Buscar manual..."
                       style="width:100%;padding:10px 12px 10px 36px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.85rem;">
            </div>
            <button type="submit"
                    style="padding:10px 20px;background:linear-gradient(135deg,#2980b9,#3498db);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:0.85rem;cursor:pointer;">
                <i class="fas fa-search me-1"></i> Buscar
            </button>
            @if(request('buscar') || request('categoria'))
            <a href="{{ route('manuales.index') }}"
               style="padding:10px 16px;background:#f7f9fc;border:1.5px solid #e2e8f0;color:#718096;border-radius:8px;font-size:0.85rem;text-decoration:none;">
                <i class="fas fa-times me-1"></i> Limpiar
            </a>
            @endif
        </form>

        @if($manuales->isEmpty())
        <div class="content-card" style="padding:48px;text-align:center;">
            <i class="fas fa-file-pdf" style="font-size:3rem;color:#e2e8f0;margin-bottom:16px;display:block;"></i>
            <h3 style="color:#718096;font-weight:600;margin-bottom:8px;">No hay manuales disponibles</h3>
            <p style="color:#a0aec0;font-size:0.85rem;">El equipo de soporte está preparando los manuales. Vuelve pronto.</p>
        </div>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
            @foreach($manuales as $manual)
            <div style="background:#fff;border-radius:12px;border:1px solid #e8ecf0;overflow:hidden;
                        box-shadow:0 2px 8px rgba(0,0,0,0.05);transition:transform .15s,box-shadow .15s;"
                 onmouseenter="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.1)'"
                 onmouseleave="this.style.transform='translateY(0)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)'">

                {{-- Header rojo PDF --}}
                <div style="background:linear-gradient(135deg,#dc2626,#ef4444);padding:20px 18px;position:relative;overflow:hidden;">
                    <div style="position:absolute;right:-10px;top:-10px;width:70px;height:70px;background:rgba(255,255,255,0.08);border-radius:50%;"></div>
                    <i class="fas fa-file-pdf" style="font-size:2.2rem;color:rgba(255,255,255,0.9);"></i>
                    @if($manual->categoria)
                    <span style="position:absolute;top:12px;right:14px;background:rgba(255,255,255,0.2);color:#fff;
                                 font-size:0.68rem;font-weight:700;padding:3px 8px;border-radius:10px;text-transform:uppercase;">
                        {{ $manual->categoria }}
                    </span>
                    @endif
                </div>

                {{-- Contenido --}}
                <div style="padding:16px 18px;">
                    <h3 style="font-size:0.95rem;font-weight:700;color:#1a2332;margin:0 0 6px;line-height:1.3;">
                        {{ $manual->titulo }}
                    </h3>
                    @if($manual->descripcion)
                    <p style="font-size:0.8rem;color:#718096;margin:0 0 12px;line-height:1.5;">
                        {{ Str::limit($manual->descripcion, 100) }}
                    </p>
                    @endif
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;">
                        <div style="font-size:0.73rem;color:#a0aec0;">
                            <i class="fas fa-weight-hanging me-1"></i>{{ $manual->tamano_formateado }}
                            <span style="margin:0 6px;">·</span>
                            <i class="fas fa-download me-1"></i>{{ $manual->downloads_count }} descargas
                        </div>
                        <a href="{{ route('manuales.download', $manual) }}"
                           style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;
                                  background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;
                                  border-radius:7px;font-size:0.78rem;font-weight:700;text-decoration:none;
                                  transition:opacity .15s;"
                           onmouseenter="this.style.opacity='.85'"
                           onmouseleave="this.style.opacity='1'">
                            <i class="fas fa-download"></i> Descargar
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection

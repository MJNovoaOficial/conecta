@extends("layouts.app")
@section("title", "Mi Rendimiento - Conecta")
@section("content")
<div class="page-wrapper">

    {{-- SIDEBAR --}}
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-headset me-2"></i>Soporte</span>
            </div>
            <a href="{{ route("tickets.index") }}" class="sidebar-item">
                <div class="item-left"><span class="item-icon"><i class="fas fa-ticket-alt"></i></span>Todos los Tickets</div>
            </a>
            <a href="{{ route("tickets.index", ["agent_id" => Auth::id()]) }}" class="sidebar-item">
                <div class="item-left"><span class="item-icon"><i class="fas fa-user-check"></i></span>Mis Tickets</div>
            </a>
            <a href="{{ route("tickets.my-stats") }}" class="sidebar-item active">
                <div class="item-left"><span class="item-icon"><i class="fas fa-chart-bar"></i></span>Mi Rendimiento</div>
            </a>
            <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#newTicketModal" onclick="this.blur()">
                <div class="item-left"><span class="item-icon"><i class="fas fa-plus-circle"></i></span>Abrir Ticket</div>
            </a>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <div class="main-content">
        <div class="page-header">
            <h1><i class="fas fa-chart-bar" style="color:#4f8cff;margin-right:8px;"></i>Mi Rendimiento</h1>
            <div class="breadcrumb-bar">
                <a href="{{ route("home") }}">Inicio</a>
                <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                <a href="{{ route("tickets.index") }}">Tickets</a>
                <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                <span>Mi Rendimiento</span>
            </div>
        </div>

        {{-- KPI cards --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:22px;">
            <div class="content-card" style="padding:20px;text-align:center;">
                <div style="font-size:1.9rem;font-weight:800;color:#1a2332;">{{ $stats["total"] }}</div>
                <div style="font-size:0.78rem;color:#718096;margin-top:4px;">Tickets Atendidos</div>
            </div>
            <div class="content-card" style="padding:20px;text-align:center;">
                <div style="font-size:1.9rem;font-weight:800;color:#22c55e;">{{ $stats["resolved"] + $stats["closed"] }}</div>
                <div style="font-size:0.78rem;color:#718096;margin-top:4px;">Resueltos / Cerrados</div>
            </div>
            <div class="content-card" style="padding:20px;text-align:center;">
                <div style="font-size:1.9rem;font-weight:800;color:#f59e0b;">{{ $stats["in_progress"] }}</div>
                <div style="font-size:0.78rem;color:#718096;margin-top:4px;">En Proceso</div>
            </div>
            <div class="content-card" style="padding:20px;text-align:center;">
                <div style="font-size:1.9rem;font-weight:800;color:#4f8cff;">{{ $stats["open"] }}</div>
                <div style="font-size:0.78rem;color:#718096;margin-top:4px;">Sin Asignar</div>
            </div>
            <div class="content-card" style="padding:20px;text-align:center;">
                <div style="font-size:1.9rem;font-weight:800;color:#8b5cf6;">
                    @if($avgHours !== null)
                        {{ round($avgHours, 1) }}<small style="font-size:1rem;font-weight:500;">h</small>
                    @else
                        —
                    @endif
                </div>
                <div style="font-size:0.78rem;color:#718096;margin-top:4px;">Prom. Resolución</div>
            </div>
        </div>

        {{-- Gráfico de barras --}}
        <div class="content-card" style="padding:24px;">
            <h3 style="margin:0 0 20px;font-size:1rem;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-chart-bar" style="color:#4f8cff;"></i>
                Tickets resueltos — Últimos 6 meses
            </h3>
            @if(array_sum($values) === 0)
                <div style="text-align:center;padding:40px 0;color:#a0aec0;">
                    <i class="fas fa-chart-bar" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
                    <p style="margin:0;font-size:0.9rem;">Aún no tienes tickets resueltos para mostrar.</p>
                </div>
            @else
                <canvas id="myChart" height="90"></canvas>
            @endif
        </div>

        {{-- Tasa de resolución --}}
        @if($stats["total"] > 0)
        <div class="content-card" style="padding:24px;margin-top:20px;">
            <h3 style="margin:0 0 16px;font-size:1rem;font-weight:700;color:#1a2332;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-trophy" style="color:#f59e0b;"></i> Tasa de resolución
            </h3>
            @php
                $tasaRes = round(($stats["resolved"] + $stats["closed"]) / $stats["total"] * 100);
                $color = $tasaRes >= 75 ? "#22c55e" : ($tasaRes >= 50 ? "#f59e0b" : "#ef4444");
            @endphp
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <div style="flex:1;min-width:200px;">
                    <div style="background:#f0f4f8;border-radius:999px;height:12px;overflow:hidden;">
                        <div style="width:{{ $tasaRes }}%;height:100%;background:{{ $color }};border-radius:999px;transition:width 1s ease;"></div>
                    </div>
                </div>
                <div style="font-size:1.5rem;font-weight:800;color:{{ $color }};">{{ $tasaRes }}%</div>
            </div>
            <p style="margin:10px 0 0;font-size:0.8rem;color:#718096;">
                {{ $stats["resolved"] + $stats["closed"] }} de {{ $stats["total"] }} tickets asignados han sido resueltos o cerrados.
            </p>
        </div>
        @endif
    </div>
</div>
@endsection

@section("scripts")
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if(array_sum($values) > 0)
const ctx = document.getElementById("myChart").getContext("2d");
new Chart(ctx, {
    type: "bar",
    data: {
        labels: {!! json_encode($labels) !!},
        datasets: [{
            label: "Tickets resueltos",
            data: {!! json_encode($values) !!},
            backgroundColor: "rgba(79,140,255,0.7)",
            borderColor: "#4f8cff",
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => " " + ctx.parsed.y + " tickets" } }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
@endif
</script>
@endsection

@include('partials.create_ticket_modal')

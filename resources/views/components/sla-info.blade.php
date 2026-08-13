@props(['ticket'])
@php
    $now = \Carbon\Carbon::now();
    $deadline = $ticket->sla_resolution_deadline_at;
    $slaEstado = $now->gt($deadline) ? 'exceeded' : ($deadline->diffInHours($now) < 12 ? 'warning' : 'on_time');
    $slaColor = $slaEstado === 'exceeded' ? '#ef4444' : ($slaEstado === 'warning' ? '#f59e0b' : '#22c55e');
    $slaRestante = $now->lt($deadline) ? $now->diffForHumans($deadline, ['parts' => 2, 'short' => true]) : null;
@endphp
<div class="flex items-center space-x-2" style="font-family: 'Inter', sans-serif;">
    <x-sla-badge :status="$slaEstado" />
    <span class="text-sm" style="color: {{ $slaColor }};">
        @if($slaEstado === 'exceeded')
            ⚠
        @elseif($slaEstado === 'warning')
            ⏰
        @else
            ✓
        @endif
        {{ $ticket->sla_resolution_deadline_at->format('d/m/Y H:i') }}
    </span>
    @if($slaRestante)
        <span class="text-xs font-medium" style="color: {{ $slaColor }};">
            {{ $slaRestante }}
        </span>
    @endif
</div>

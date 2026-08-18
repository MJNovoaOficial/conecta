@props(['ticket'])
@php
    // El estado y el tiempo restante los calcula el modelo. Antes se resolvían
    // aquí comparando el plazo contra la fecha de hoy, lo que marcaba como
    // vencido cualquier ticket antiguo aunque se hubiera atendido a tiempo.
    $slaEstado = $ticket->getSlaResolutionStatus();
    $slaRestante = $ticket->getSlaRemainingFormatted();

    $slaColor = match ($slaEstado) {
        'exceeded' => '#ef4444',
        'warning'  => '#f59e0b',
        'ok'       => '#22c55e',
        default    => '#94a3b8',
    };
@endphp
@if($slaEstado !== 'none')
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
@endif

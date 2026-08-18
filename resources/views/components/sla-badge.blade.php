@props(['status'])
@php
    // Los estados vienen de Ticket::getSlaResolutionStatus(): 'ok', 'warning',
    // 'exceeded' o 'none'. Se acepta 'on_time' como sinónimo de 'ok' porque así
    // lo llamaba la ficha del ticket antes de unificar el cálculo.
    //
    // El caso por defecto NO puede ser el rojo: antes, un ticket sano devolvía
    // 'ok', no coincidía con ninguna rama y terminaba mostrando "SLA Vencida".
    $estado = in_array($status, ['ok', 'on_time'], true) ? 'ok' : $status;
@endphp
@if(in_array($estado, ['ok', 'warning', 'exceeded'], true))
<div class="sla-badge inline-block px-3 py-1 rounded-full text-sm font-medium"
     style="font-family: 'Inter', sans-serif;">
    @if($estado === 'ok')
        <span class="bg-green-200 text-green-800">SLA OK</span>
    @elseif($estado === 'warning')
        <span class="bg-yellow-200 text-yellow-800">SLA Próxima</span>
    @else
        <span class="bg-red-200 text-red-800">SLA Vencida</span>
    @endif
</div>
@endif

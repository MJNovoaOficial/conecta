@props(['status'])
<div class="sla-badge inline-block px-3 py-1 rounded-full text-sm font-medium"
     style="font-family: 'Inter', sans-serif;">
    @if($status === 'on_time')
        <span class="bg-green-200 text-green-800">SLA OK</span>
    @elseif($status === 'warning')
        <span class="bg-yellow-200 text-yellow-800">SLA Próxima</span>
    @else
        <span class="bg-red-200 text-red-800">SLA Vencida</span>
    @endif
</div>

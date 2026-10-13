@php($limits = \App\Support\TicketAttachmentLimits::forRequest($forComments ?? false))
<small style="display:block;font-size:.95rem;color:#718096;margin:6px 0;overflow-wrap:anywhere;">
    Máximo {{ $limits['files'] }} {{ $limits['files'] === 1 ? 'archivo' : 'archivos' }}, hasta {{ $limits['kilobytes']/1024 }} MB por archivo.
</small>

@if(\App\Support\ProjectLogAccess::staff(auth()->user()) && \App\Support\ProjectLogAccess::available())
<a href="{{ route('project-log.index') }}" class="project-log-nav" style="display:flex;align-items:center;gap:6px;font-size:.82rem;color:#e2e8f0;text-decoration:none;padding:5px 10px;">
    <i class="fas fa-book-open" aria-hidden="true"></i> Bitácora
</a>
@endif

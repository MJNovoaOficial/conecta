@extends('layouts.app')
@section('title', 'Manuales — Admin')

@section('content')
<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'manuales'])
    <div class="admin-content-wrap">
        <div class="admin-wrapper">

            <div class="admin-page-header">
                <div>
                    <h1><i class="fas fa-file-pdf" style="color:#ef4444;margin-right:8px;"></i>Manuales Descargables</h1>
                    <p style="color:#718096;font-size:0.83rem;margin:0;">PDFs de ayuda para que los usuarios resuelvan problemas de forma autónoma.</p>
                </div>
                <a href="{{ route('admin.manuales.create') }}" class="btn-back-admin"
                   style="background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border-color:transparent;">
                    <i class="fas fa-plus me-1"></i> Subir Manual
                </a>
            </div>

            @if(session('success'))
            <div style="background:#e6f7ed;border:1px solid #a7d7b3;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#1a7f43;font-size:0.85rem;display:flex;gap:8px;align-items:center;">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
            @endif

            <div class="admin-card" style="padding:0;overflow:hidden;">
                @if($manuales->isEmpty())
                <div style="padding:48px;text-align:center;">
                    <i class="fas fa-file-pdf" style="font-size:3rem;color:#e2e8f0;margin-bottom:16px;display:block;"></i>
                    <p style="color:#a0aec0;font-size:0.85rem;">No hay manuales aún. Usa el botón "Subir Manual" para agregar el primero.</p>
                </div>
                @else
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f7f9fc;border-bottom:1.5px solid #e8ecf0;">
                            <th style="padding:12px 18px;text-align:left;font-size:0.78rem;color:#718096;font-weight:700;">TÍTULO</th>
                            <th style="padding:12px 12px;text-align:left;font-size:0.78rem;color:#718096;font-weight:700;">CATEGORÍA</th>
                            <th style="padding:12px 12px;text-align:center;font-size:0.78rem;color:#718096;font-weight:700;">TAMAÑO</th>
                            <th style="padding:12px 12px;text-align:center;font-size:0.78rem;color:#718096;font-weight:700;">DESCARGAS</th>
                            <th style="padding:12px 12px;text-align:center;font-size:0.78rem;color:#718096;font-weight:700;">ESTADO</th>
                            <th style="padding:12px 18px;text-align:right;font-size:0.78rem;color:#718096;font-weight:700;">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($manuales as $manual)
                        <tr style="border-bottom:1px solid #f0f2f5;" onmouseenter="this.style.background='#fafbfc'" onmouseleave="this.style.background='#fff'">
                            <td style="padding:14px 18px;">
                                <div style="font-size:0.88rem;font-weight:600;color:#1a2332;">
                                    <i class="fas fa-file-pdf" style="color:#ef4444;margin-right:6px;"></i>
                                    {{ $manual->titulo }}
                                </div>
                                @if($manual->descripcion)
                                <div style="font-size:0.75rem;color:#a0aec0;margin-top:2px;">{{ Str::limit($manual->descripcion, 80) }}</div>
                                @endif
                            </td>
                            <td style="padding:14px 12px;">
                                @if($manual->categoria)
                                <span style="background:#fef3c7;color:#92400e;font-size:0.72rem;font-weight:700;padding:3px 8px;border-radius:10px;">
                                    {{ ucfirst($manual->categoria) }}
                                </span>
                                @else
                                <span style="color:#a0aec0;font-size:0.78rem;">—</span>
                                @endif
                            </td>
                            <td style="padding:14px 12px;text-align:center;font-size:0.82rem;color:#718096;">
                                {{ $manual->tamano_formateado }}
                            </td>
                            <td style="padding:14px 12px;text-align:center;">
                                <span style="font-size:0.88rem;font-weight:700;color:#3b82f6;">{{ $manual->downloads_count }}</span>
                            </td>
                            <td style="padding:14px 12px;text-align:center;">
                                @if($manual->is_active)
                                <span style="background:#dcfce7;color:#166534;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:10px;">Activo</span>
                                @else
                                <span style="background:#f3f4f6;color:#9ca3af;font-size:0.72rem;font-weight:700;padding:3px 10px;border-radius:10px;">Archivado</span>
                                @endif
                            </td>
                            <td style="padding:14px 18px;text-align:right;">
                                <div style="display:flex;gap:6px;justify-content:flex-end;">
                                    <a href="{{ route('manuales.download', $manual) }}"
                                       title="Descargar"
                                       style="padding:5px 10px;background:#f0f2f5;color:#718096;border-radius:6px;font-size:0.78rem;text-decoration:none;">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="{{ route('admin.manuales.edit', $manual) }}"
                                       style="padding:5px 10px;background:#dbeafe;color:#1d4ed8;border-radius:6px;font-size:0.78rem;text-decoration:none;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.manuales.destroy', $manual) }}"
                                          onsubmit="return confirm('¿Eliminar este manual? Esta acción no se puede deshacer.')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                style="padding:5px 10px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:0.78rem;cursor:pointer;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection

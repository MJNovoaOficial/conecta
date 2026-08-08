@extends('errors.layout')

@section('codigo', '500')
@section('titulo', 'Algo salió mal')
@section('color', 'rgba(231,76,60,0.18)')

@section('icono')
    <svg viewBox="0 0 24 24" style="stroke:#f08a80;">
        <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path>
        <path d="M12 9v4"></path>
        <path d="M12 17h.01"></path>
    </svg>
@endsection

@section('mensaje')
    Ocurrió un error inesperado en el sistema. No es culpa tuya y el problema ya
    quedó registrado.
@endsection

@section('detalle')
    {{-- Este dato le sirve al equipo para ubicar el error en los registros.
         No usa base de datos ni sesión: solo la hora del servidor. --}}
    Si necesitas reportarlo, menciona esta hora:<br>
    <strong>{{ now()->format('d/m/Y H:i:s') }}</strong>
@endsection

@section('acciones')
    <a href="{{ url('/') }}" class="btn-err btn-err-primario">Volver al inicio</a>
    <a href="mailto:{{ config('mail.from.address') }}?subject=Error%20500%20en%20Conecta%20-%20{{ now()->format('d/m/Y H:i:s') }}"
       class="btn-err btn-err-secundario">Reportar el problema</a>
@endsection

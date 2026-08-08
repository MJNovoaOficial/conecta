@extends('errors.layout')

@section('codigo', '403')
@section('titulo', 'Acceso denegado')
@section('color', 'rgba(230,126,34,0.18)')

@section('icono')
    <svg viewBox="0 0 24 24" style="stroke:#f0a860;">
        <rect x="3" y="11" width="18" height="11" rx="2"></rect>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
    </svg>
@endsection

@section('mensaje')
    No tienes permiso para acceder a esta página.
@endsection

@section('detalle')
    Si crees que deberías tener acceso, coméntaselo a tu jefatura o abre un ticket
    de soporte indicando qué estabas intentando hacer.
@endsection

@section('acciones')
    <a href="{{ url('/') }}" class="btn-err btn-err-primario">Volver al inicio</a>
@endsection

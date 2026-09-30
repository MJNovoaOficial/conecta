@extends('errors.layout')

@section('codigo', '419')
@section('titulo', 'La página ha expirado')
@section('color', 'rgba(52,152,219,0.18)')

@section('icono')
    <svg viewBox="0 0 24 24" style="stroke:#90cdf4;">
        <circle cx="12" cy="12" r="9"></circle>
        <path d="M12 7v5l3 2"></path>
    </svg>
@endsection

@section('mensaje')
    Tu sesión o esta página han expirado. Vuelve a iniciar sesión para continuar.
@endsection

@section('detalle')
    Si estabas completando un formulario, revísalo y envíalo nuevamente después de iniciar sesión.
@endsection

@section('acciones')
    <a href="{{ route('login') }}" class="btn-err btn-err-primario">Volver a iniciar sesión</a>
@endsection

@extends('errors.layout')

@section('codigo', '404')
@section('titulo', 'Página no encontrada')
@section('color', 'rgba(52,152,219,0.18)')

@section('icono')
    <svg viewBox="0 0 24 24" style="stroke:#7cc3ef;">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
    </svg>
@endsection

@section('mensaje')
    La página que buscas no existe, cambió de dirección o el contenido fue eliminado.
@endsection

@section('detalle')
    Revisa que la dirección esté bien escrita. Si llegaste desde un enlace dentro
    del sistema, avísanos: puede que haya quedado apuntando a algo que ya no está.
@endsection

@section('acciones')
    <a href="{{ url('/') }}" class="btn-err btn-err-primario">Volver al inicio</a>
@endsection

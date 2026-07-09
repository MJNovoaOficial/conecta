@extends('layouts.app')

@section('title', 'Demasiados intentos')

@section('content')
<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card text-center">
            <div class="card-body" style="padding: 40px;">
                <i class="fas fa-shield-alt" style="font-size: 50px; color: #e74c3c;"></i>
                <h3 class="mt-3">Demasiados intentos</h3>
                <p class="text-muted">
                    Has excedido el número máximo de intentos permitidos.
                </p>
                <p>
                    Por favor, espera 
                    <strong>{{ isset($seconds) ? gmdate('i:s', $seconds) : 'unos minutos' }}</strong>
                    antes de intentar nuevamente.
                </p>
                <a href="/" class="btn btn-primary mt-3">
                    <i class="fas fa-home"></i> Volver al inicio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

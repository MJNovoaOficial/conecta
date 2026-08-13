{{--
    Base para las páginas de error (403, 404, 500).

    IMPORTANTE: no extiende layouts.app a propósito. Ese layout consulta la base
    de datos (nombre del usuario, campana de notificaciones), así que si el error
    fue justamente un fallo de base de datos, la página de error volvería a
    fallar y el usuario vería la pantalla genérica igual.

    Por el mismo motivo no se cargan iconos ni estilos desde CDN: el icono es un
    SVG en línea. Esta página debe poder mostrarse aunque no haya nada más vivo.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('codigo') · Conecta Mesa de Ayuda</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            color: #2d3748;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .err-card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            overflow: hidden;
        }

        .err-top {
            background: linear-gradient(135deg, #1a2332 0%, #243447 100%);
            padding: 28px 24px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .err-top::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 150px; height: 150px;
            border-radius: 50%;
            background: rgba(52,152,219,0.12);
        }

        .err-icon {
            width: 58px; height: 58px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            position: relative;
        }
        .err-icon svg { width: 28px; height: 28px; stroke: #fff; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

        .err-codigo {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: #90cdf4;
            position: relative;
        }
        .err-titulo {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin-top: 5px;
            position: relative;
        }

        .err-cuerpo { padding: 24px; text-align: center; }
        .err-mensaje { font-size: 0.9rem; line-height: 1.65; color: #4a5568; }
        .err-detalle {
            margin-top: 14px;
            padding: 10px 12px;
            background: #f7f9fc;
            border: 1px solid #e8ecf0;
            border-radius: 7px;
            font-size: 0.78rem;
            color: #718096;
            line-height: 1.5;
        }

        .err-acciones {
            padding: 0 24px 24px;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }
        .btn-err {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 18px;
            border-radius: 7px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-err-primario {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: #fff;
            box-shadow: 0 2px 8px rgba(41,128,185,0.3);
        }
        .btn-err-primario:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(41,128,185,0.4); }
        .btn-err-secundario {
            background: #fff;
            color: #4a5568;
            border: 1.5px solid #cbd5e0;
        }
        .btn-err-secundario:hover { background: #f7f9fc; border-color: #a0aec0; color: #2d3748; }

        .err-pie {
            text-align: center;
            font-size: 0.75rem;
            color: #a0aec0;
            margin-top: 18px;
        }
    </style>
</head>
<body>
    <div>
        <div class="err-card">
            <div class="err-top">
                <div class="err-icon" style="background: @yield('color');">
                    @yield('icono')
                </div>
                <div class="err-codigo">Error @yield('codigo')</div>
                <div class="err-titulo">@yield('titulo')</div>
            </div>

            <div class="err-cuerpo">
                <p class="err-mensaje">@yield('mensaje')</p>
                @hasSection('detalle')
                    <div class="err-detalle">@yield('detalle')</div>
                @endif
            </div>

            <div class="err-acciones">
                @yield('acciones')
            </div>
        </div>

        <p class="err-pie">Conecta · Mesa de Ayuda Dimak</p>
    </div>
</body>
</html>

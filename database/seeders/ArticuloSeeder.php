<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Artículos iniciales de la base de conocimiento (RN-18).
 *
 * Están redactados a partir de los problemas que más se repiten en los tickets
 * existentes, priorizando los que un usuario puede resolver solo.
 */
class ArticuloSeeder extends Seeder
{
    public function run(): void
    {
        $autorId = User::where('role', 'admin')->value('id');

        $articulos = [
            [
                'categoria' => 'Hardware',
                'title'     => 'El monitor no enciende o la pantalla queda en negro',
                'symptoms'  => 'monitor apagado, pantalla negra, no da señal, sin imagen, no prende la pantalla, display sin video',
                'content'   => "1. Revisa que el cable de poder del monitor esté firme en el enchufe y en la parte trasera del monitor.\n"
                             . "2. Comprueba si el monitor tiene una luz encendida. Si no hay ninguna luz, el problema es de alimentación: prueba con otro enchufe.\n"
                             . "3. Verifica que el cable de video (HDMI, DisplayPort o VGA) esté bien conectado en los dos extremos.\n"
                             . "4. Si el monitor enciende pero dice \"sin señal\", presiona el botón de fuente o entrada del monitor hasta encontrar la correcta.\n"
                             . "5. Asegúrate de que el computador esté encendido y no suspendido: mueve el mouse o presiona una tecla.\n\n"
                             . "Si después de esto sigue sin imagen, abre un ticket indicando qué luces ves en el monitor.",
            ],
            [
                'categoria' => 'Hardware',
                'title'     => 'La impresora no imprime',
                'symptoms'  => 'impresora no imprime, no sale la hoja, trabajo en cola, impresora sin responder, no imprime nada',
                'content'   => "1. Verifica que la impresora esté encendida y sin luces de error parpadeando.\n"
                             . "2. Revisa que tenga papel y que la bandeja esté bien cerrada.\n"
                             . "3. Comprueba el nivel de tóner. Si aparece un aviso de tóner bajo, avísale a tu jefatura para pedir el repuesto.\n"
                             . "4. En Windows, abre Configuración → Impresoras y escáneres y confirma que sea la impresora correcta (no una antigua o \"Microsoft Print to PDF\").\n"
                             . "5. Abre la cola de impresión y cancela los trabajos atascados. A veces un documento con error bloquea todos los siguientes.\n"
                             . "6. Apaga la impresora, espera 30 segundos y vuelve a encenderla.\n\n"
                             . "Si el problema persiste o la impresora aparece \"sin conexión\", abre un ticket indicando el nombre de la impresora.",
            ],
            [
                'categoria' => 'Cuenta / Acceso',
                'title'     => 'Mi cuenta quedó bloqueada por intentos fallidos',
                'symptoms'  => 'cuenta bloqueada, contraseña bloqueada, active directory bloqueado, no me deja entrar, demasiados intentos',
                'content'   => "Las cuentas se bloquean automáticamente después de varios intentos con contraseña incorrecta. Es una medida de seguridad.\n\n"
                             . "1. Espera 15 minutos: en muchos casos el bloqueo se libera solo.\n"
                             . "2. Comprueba que la tecla Bloq Mayús no esté activada.\n"
                             . "3. Verifica la distribución del teclado. En teclado latinoamericano la arroba se escribe con AltGr + Q, no con Shift + 2.\n"
                             . "4. Si tienes la sesión abierta en el teléfono con la contraseña antigua, ciérrala: los reintentos automáticos pueden estar bloqueándote la cuenta.\n\n"
                             . "Si pasados los 15 minutos sigue bloqueada, abre un ticket para que soporte la desbloquee.",
            ],
            [
                'categoria' => 'Software',
                'title'     => 'No se escucha el audio en reuniones de Microsoft Teams',
                'symptoms'  => 'teams sin audio, no escucho, no me escuchan, microfono no funciona, sin sonido en reunion',
                'content'   => "1. Revisa que el volumen del computador no esté en silencio y que el ícono de altavoz no tenga una equis.\n"
                             . "2. Si usas audífonos, comprueba que estén bien conectados y que no tengan su propio botón de silencio.\n"
                             . "3. Dentro de la reunión, abre los tres puntos → Configuración de dispositivo y confirma que el altavoz y el micrófono seleccionados sean los que estás usando.\n"
                             . "4. Verifica que no tengas el micrófono silenciado dentro de Teams (el ícono aparece tachado).\n"
                             . "5. Si nada funciona, sal de la reunión y vuelve a entrar. Teams a veces toma mal el dispositivo al iniciar.\n\n"
                             . "Si el problema se repite en todas las reuniones, abre un ticket indicando qué audífonos o parlantes usas.",
            ],
            [
                'categoria' => 'Hardware',
                'title'     => 'La cámara web no funciona en videollamadas',
                'symptoms'  => 'webcam no funciona, camara no enciende, no se ve mi video, camara negra, zoom sin camara',
                'content'   => "1. Comprueba que la cámara no tenga la tapa física cerrada. Muchos notebooks traen un obturador deslizante.\n"
                             . "2. Cierra otras aplicaciones que puedan estar usando la cámara: solo un programa puede ocuparla a la vez.\n"
                             . "3. En Windows, entra a Configuración → Privacidad y seguridad → Cámara y confirma que el acceso esté permitido para la aplicación que usas.\n"
                             . "4. Dentro de la videollamada, revisa en la configuración que esté seleccionada la cámara correcta.\n"
                             . "5. Si es una cámara externa, prueba conectarla en otro puerto USB.\n\n"
                             . "Si la cámara no aparece en ninguna aplicación, abre un ticket: puede ser un problema de controlador.",
            ],
            [
                'categoria' => 'Hardware',
                'title'     => 'El teclado o mouse inalámbrico dejó de responder',
                'symptoms'  => 'teclado inalambrico no responde, mouse no funciona, teclas que no escriben, teclado sin bateria',
                'content'   => "1. Cambia las pilas. Es la causa más frecuente y no siempre avisa antes de fallar.\n"
                             . "2. Revisa que el receptor USB esté bien conectado. Prueba cambiarlo de puerto.\n"
                             . "3. Comprueba que el teclado tenga su interruptor de encendido activado (suele estar en la parte inferior).\n"
                             . "4. Si solo fallan algunas teclas, puede ser suciedad: da vuelta el teclado y sacúdelo con cuidado.\n"
                             . "5. Aleja el receptor de otros dispositivos inalámbricos que puedan interferir.\n\n"
                             . "Si con pilas nuevas sigue sin responder, abre un ticket para solicitar el reemplazo.",
            ],
            [
                'categoria' => 'Red / Internet',
                'title'     => 'La VPN no conecta desde la casa',
                'symptoms'  => 'vpn no conecta, no puedo entrar remoto, error de vpn, trabajo remoto sin conexion, vpn se desconecta',
                'content'   => "1. Confirma que tengas internet: abre cualquier página web antes de intentar la VPN.\n"
                             . "2. Cierra la aplicación de VPN por completo y vuelve a abrirla.\n"
                             . "3. Reinicia tu módem o router: desenchúfalo 30 segundos y vuelve a conectarlo.\n"
                             . "4. Si estás usando los datos del teléfono, algunas compañías bloquean las conexiones VPN. Prueba con otra red.\n"
                             . "5. Verifica que tu usuario y contraseña sean los mismos del computador de la oficina.\n\n"
                             . "Si aparece un mensaje de error, abre un ticket copiando el texto exacto del mensaje.",
            ],
            [
                'categoria' => 'Hardware',
                'title'     => 'El monitor secundario no es detectado',
                'symptoms'  => 'segunda pantalla no aparece, monitor secundario no detectado, no reconoce el segundo monitor, pantalla extendida',
                'content'   => "1. Revisa que el cable de video del segundo monitor esté firme en los dos extremos.\n"
                             . "2. Presiona la tecla Windows + P y elige \"Extender\". Es lo que más se olvida.\n"
                             . "3. En Configuración → Sistema → Pantalla, presiona \"Detectar\".\n"
                             . "4. Si usas una base o adaptador, desconéctalo y vuelve a conectarlo.\n"
                             . "5. Prueba el monitor secundario como único monitor para descartar que el problema sea de la pantalla.\n\n"
                             . "Si después de esto sigue sin aparecer, abre un ticket indicando el modelo del monitor y cómo está conectado.",
            ],
            [
                'categoria' => 'Red / Internet',
                'title'     => 'No puedo acceder a las carpetas compartidas del servidor',
                'symptoms'  => 'carpeta compartida no abre, sin acceso al servidor, unidad de red desconectada, no encuentro la carpeta',
                'content'   => "1. Comprueba que tengas conexión a la red de la empresa. Si trabajas desde casa, necesitas tener la VPN conectada.\n"
                             . "2. Abre el Explorador de archivos y revisa si la unidad de red aparece con una equis roja. Si es así, haz doble clic para reconectarla.\n"
                             . "3. Reinicia el computador: las unidades de red se reconectan al iniciar sesión.\n"
                             . "4. Si te pide usuario y contraseña, usa los mismos con los que entras al computador.\n\n"
                             . "Si te dice que no tienes permisos, abre un ticket indicando la ruta exacta de la carpeta: es probable que necesites autorización.",
            ],
            [
                'categoria' => 'Software',
                'title'     => 'Outlook no sincroniza los correos',
                'symptoms'  => 'outlook no recibe correos, no sincroniza, correo desactualizado, bandeja de entrada vacia, error de sincronizacion',
                'content'   => "1. Revisa abajo a la derecha de Outlook: si dice \"Trabajando sin conexión\", haz clic en la pestaña Enviar y recibir y desactiva esa opción.\n"
                             . "2. Presiona F9 para forzar el envío y recepción.\n"
                             . "3. Comprueba que tengas conexión a internet abriendo el correo desde el navegador.\n"
                             . "4. Cierra Outlook por completo y vuelve a abrirlo.\n"
                             . "5. Si tu buzón está lleno, deja de recibir correos: revisa el espacio disponible y borra elementos grandes o vacía la papelera.\n\n"
                             . "Si aparece un mensaje de error al sincronizar, abre un ticket copiando el texto exacto.",
            ],
        ];

        foreach ($articulos as $datos) {
            $categoriaId = \App\Models\Categoria::where('name', $datos['categoria'])->value('id');

            Articulo::firstOrCreate(
                ['title' => $datos['title']],
                [
                    'symptoms'     => $datos['symptoms'],
                    'content'      => $datos['content'],
                    'categoria_id' => $categoriaId,
                    'is_active'    => true,
                    'created_by'   => $autorId,
                ]
            );
        }

        $this->command->info('Base de conocimiento: ' . count($articulos) . ' artículos cargados.');
    }
}

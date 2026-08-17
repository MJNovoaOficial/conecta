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

            // ── Software ──────────────────────────────────────────────
            [
                'categoria' => 'Software',
                'title'     => 'Excel o Word se cierra solo o deja de responder',
                'symptoms'  => 'excel se cierra, word no responde, office se cuelga, se congela, deja de funcionar',
                'content'   => "1. Espera 30 segundos antes de forzar el cierre: muchas veces está procesando y se recupera solo.\n"
                             . "2. Si el archivo es muy pesado o tiene muchas fórmulas, cierra los demás programas para liberar memoria.\n"
                             . "3. Vuelve a abrir el programa: Office suele ofrecer recuperar el documento no guardado.\n"
                             . "4. Si el problema es siempre con el mismo archivo, cópialo a tu escritorio y ábrelo desde ahí. Los archivos en carpetas de red fallan más.\n"
                             . "5. Guarda tu trabajo con frecuencia mientras tanto.\n\n"
                             . "Si se cierra con cualquier archivo, abre un ticket indicando la versión de Office.",
            ],
            [
                'categoria' => 'Software',
                'title'     => 'No puedo abrir un archivo PDF',
                'symptoms'  => 'pdf no abre, no se ve el pdf, error al abrir pdf, archivo dañado',
                'content'   => "1. Prueba abrirlo con el navegador: arrastra el archivo a una pestaña de Chrome o Edge.\n"
                             . "2. Si se abre en el navegador pero no en el lector, el problema es del programa, no del archivo.\n"
                             . "3. Si el archivo llegó por correo, descárgalo primero al computador en vez de abrirlo desde el correo.\n"
                             . "4. Verifica que el archivo se haya descargado completo: si pesa 0 KB, vuelve a bajarlo.\n\n"
                             . "Si el PDF está protegido con contraseña y no la tienes, pídesela a quien te lo envió.",
            ],
            [
                'categoria' => 'Software',
                'title'     => 'Un programa no abre o se queda cargando',
                'symptoms'  => 'programa no abre, se queda cargando, no inicia, aplicacion no responde al abrir',
                'content'   => "1. Revisa si ya está abierto: presiona Ctrl + Shift + Esc y búscalo en la lista de procesos.\n"
                             . "2. Si aparece ahí, selecciónalo y presiona \"Finalizar tarea\". Después vuelve a abrirlo.\n"
                             . "3. Reinicia el computador. Suena obvio, pero resuelve la mayoría de estos casos.\n"
                             . "4. Comprueba que no haya una actualización pendiente esperando reinicio.\n\n"
                             . "Si después de reiniciar sigue igual, abre un ticket indicando el nombre exacto del programa.",
            ],
            [
                'categoria' => 'Software',
                'title'     => 'Office pide activar la licencia',
                'symptoms'  => 'licencia de office, producto sin licencia, activar office, version de evaluacion',
                'content'   => "Este aviso aparece cuando el equipo lleva mucho tiempo sin conectarse a la red de la empresa.\n\n"
                             . "1. Conéctate a la red de la oficina, o activa la VPN si estás fuera.\n"
                             . "2. Abre cualquier programa de Office y espera un par de minutos con la conexión activa.\n"
                             . "3. Si sigue apareciendo, cierra todos los programas de Office y vuelve a abrir uno.\n\n"
                             . "Si el aviso persiste después de un día conectado, abre un ticket: puede que la licencia deba reasignarse.",
            ],

            // ── Red / Internet ────────────────────────────────────────
            [
                'categoria' => 'Red / Internet',
                'title'     => 'Mi equipo no tiene internet',
                'symptoms'  => 'sin internet, no navega, sin conexion, no carga ninguna pagina, red desconectada',
                'content'   => "1. Mira el ícono de red junto al reloj. Si tiene una equis o un globo terráqueo, no hay conexión.\n"
                             . "2. Si usas cable, revisa que esté firme en el computador y en la roseta de pared.\n"
                             . "3. Si usas wifi, comprueba que estés conectado a la red correcta y no a una de invitados.\n"
                             . "4. Pregunta a un compañero cerca si él tiene internet: si nadie tiene, es una caída general y ya la estamos viendo.\n"
                             . "5. Reinicia el computador.\n\n"
                             . "Si eres el único sin conexión y ya reiniciaste, abre un ticket indicando tu ubicación.",
            ],
            [
                'categoria' => 'Red / Internet',
                'title'     => 'La conexión wifi se corta a cada rato',
                'symptoms'  => 'wifi se desconecta, internet intermitente, se corta la conexion, señal debil',
                'content'   => "1. Revisa la cantidad de barras de señal. Con una o dos barras los cortes son normales.\n"
                             . "2. Aléjate de microondas, impresoras y equipos grandes: interfieren con la señal.\n"
                             . "3. Si estás lejos del punto de acceso, prueba acercarte y ver si mejora.\n"
                             . "4. Olvida la red y vuelve a conectarte: Configuración → Red e Internet → Wi-Fi → Administrar redes conocidas.\n"
                             . "5. Si tu puesto es fijo, considera pedir conexión por cable: es más estable.\n\n"
                             . "Si el corte ocurre siempre en el mismo lugar de la planta, abre un ticket indicándolo: puede faltar cobertura ahí.",
            ],
            [
                'categoria' => 'Red / Internet',
                'title'     => 'Una página o sistema de la empresa no carga',
                'symptoms'  => 'no carga el sistema, pagina no disponible, sitio caido, error al entrar al portal',
                'content'   => "1. Comprueba que tengas internet abriendo otra página cualquiera.\n"
                             . "2. Si estás fuera de la oficina, confirma que la VPN esté conectada: los sistemas internos solo funcionan con ella.\n"
                             . "3. Presiona Ctrl + Shift + R para recargar ignorando la caché.\n"
                             . "4. Prueba en otro navegador o en una ventana de incógnito.\n"
                             . "5. Pregunta a un compañero si a él le carga: si a nadie le funciona, es una caída del sistema.\n\n"
                             . "Al abrir el ticket, indica la dirección exacta y el mensaje de error que aparece.",
            ],
            [
                'categoria' => 'Red / Internet',
                'title'     => 'Cómo ver mi dirección IP y el nombre de mi equipo',
                'symptoms'  => 'cual es mi ip, direccion ip, ip del computador, mi ip, nombre del equipo, hostname, soporte me pide la ip, conflicto de ip, no tengo ip',
                // Las dos rutas sin comandos van separadas y dicen dónde NO está
                // cada dato. Redactado junto no basta: al resumirlo se termina
                // afirmando que el nombre del equipo sale en las propiedades de
                // red, que es de los primeros lugares donde la gente lo busca.
                'content'   => "Soporte te pide estos datos para ubicar tu equipo en la red. Sacarlos toma menos de un minuto.\n\n"
                             . "Con comandos, los dos datos de una vez:\n"
                             . "1. Presiona la tecla Windows + R, escribe cmd y presiona Enter. Se abre una ventana negra.\n"
                             . "2. Escribe ipconfig y presiona Enter. Busca la línea \"Dirección IPv4\": ese número, con formato 10.x.x.x o 192.168.x.x, es tu dirección IP.\n"
                             . "3. En la misma ventana escribe hostname y presiona Enter. Eso te muestra el nombre del equipo.\n\n"
                             . "Sin comandos, la dirección IP está en: Configuración, Red e Internet, propiedades de la conexión que estés usando.\n\n"
                             . "Sin comandos, el nombre del equipo está en otro lugar distinto: Configuración, Sistema, y al final de la lista \"Información del sistema\". No aparece en las propiedades de red.\n\n"
                             . "Si la dirección empieza con 169.254, tu equipo no está recibiendo dirección de la red. Revisa que el cable esté firme o vuelve a conectarte al wifi, y si sigue igual abre un ticket.\n\n"
                             . "Si te aparece un aviso de conflicto de direcciones IP, abre un ticket indicando tu ubicación: eso se corrige desde la red y no lo puedes resolver tú.",
            ],

            // ── Cuenta / Acceso ───────────────────────────────────────
            [
                'categoria' => 'Cuenta / Acceso',
                'title'     => 'Olvidé mi contraseña',
                'symptoms'  => 'olvide la clave, recuperar contraseña, no recuerdo mi password, restablecer clave',
                'content'   => "1. En la pantalla de inicio de sesión del sistema, usa la opción \"¿Olvidaste tu contraseña?\".\n"
                             . "2. Te llegará un correo con un enlace para crear una nueva. Revisa también la carpeta de correo no deseado.\n"
                             . "3. La contraseña nueva debe tener al menos 12 caracteres, con mayúsculas, minúsculas, números y símbolos.\n"
                             . "4. Si es la contraseña del computador (no la del sistema de tickets), esa la restablece soporte.\n\n"
                             . "Si el correo no llega en 10 minutos, abre un ticket o pide ayuda a un compañero para reportarlo.",
            ],
            [
                'categoria' => 'Cuenta / Acceso',
                'title'     => 'No tengo permisos para entrar a un sistema o carpeta',
                'symptoms'  => 'sin permisos, acceso denegado, no autorizado, no me deja entrar al sistema',
                'content'   => "Los permisos se asignan según el cargo y el área, así que no se pueden otorgar de inmediato.\n\n"
                             . "1. Confirma que estés usando tu cuenta y no la de otra persona.\n"
                             . "2. Si antes tenías acceso y lo perdiste, menciónalo: puede ser un cambio reciente.\n"
                             . "3. Para pedir un acceso nuevo, abre un ticket indicando: a qué sistema o carpeta, para qué lo necesitas, y quién es tu jefatura.\n\n"
                             . "El acceso requiere aprobación de tu jefatura, así que el ticket puede tardar más que uno técnico.",
            ],
            [
                'categoria' => 'Cuenta / Acceso',
                'title'     => 'Necesito una cuenta para una persona nueva',
                'symptoms'  => 'cuenta nueva, alta de usuario, correo para nuevo empleado, crear usuario',
                'content'   => "Para crear una cuenta necesitamos estos datos, así que tenlos a mano antes de abrir el ticket:\n\n"
                             . "1. Nombre completo y RUT de la persona.\n"
                             . "2. Cargo y departamento.\n"
                             . "3. Fecha de ingreso.\n"
                             . "4. A qué sistemas necesitará acceso.\n"
                             . "5. Si reemplaza a alguien, indícalo: podemos replicar los permisos.\n\n"
                             . "Pídelo con al menos dos días de anticipación al ingreso, así la persona llega con todo listo.",
            ],

            // ── Seguridad ─────────────────────────────────────────────
            [
                'categoria' => 'Seguridad',
                'title'     => 'Recibí un correo sospechoso',
                'symptoms'  => 'correo sospechoso, phishing, estafa, correo raro, me piden la contraseña por correo',
                'content'   => "IMPORTANTE: no hagas clic en ningún enlace ni descargues archivos adjuntos.\n\n"
                             . "Señales de que un correo es falso:\n"
                             . "1. Pide tu contraseña o datos bancarios. Nadie de la empresa te los va a pedir por correo, nunca.\n"
                             . "2. Mete urgencia: \"tu cuenta será cerrada hoy\".\n"
                             . "3. La dirección del remitente tiene letras cambiadas o un dominio que no es el de la empresa.\n"
                             . "4. Tiene faltas de ortografía o un saludo genérico.\n\n"
                             . "Qué hacer: abre un ticket adjuntando el correo, sin reenviarlo a compañeros. Si ya hiciste clic o ingresaste tu contraseña, repórtalo de inmediato: no es un reto, es lo correcto y mientras antes lo sepamos, mejor.",
            ],
            [
                'categoria' => 'Seguridad',
                'title'     => 'El antivirus bloqueó un programa que necesito',
                'symptoms'  => 'antivirus bloquea, programa bloqueado, amenaza detectada, no me deja instalar',
                'content'   => "1. No intentes desactivar el antivirus ni saltarte el bloqueo: si lo detuvo, hay un motivo.\n"
                             . "2. Anota el nombre exacto del programa y el mensaje que mostró el antivirus.\n"
                             . "3. Si descargaste el programa de internet, indica de qué sitio.\n"
                             . "4. Abre un ticket con esos datos. Si el programa es legítimo y lo necesitas para trabajar, se autoriza.\n\n"
                             . "Nunca instales programas descargados de sitios no oficiales, aunque parezcan la misma aplicación.",
            ],
            [
                'categoria' => 'Seguridad',
                'title'     => 'Conecté un pendrive y no lo reconoce',
                'symptoms'  => 'pendrive no funciona, usb bloqueado, no reconoce el pendrive, memoria usb',
                'content'   => "Por política de seguridad, los puertos USB de almacenamiento están restringidos en la mayoría de los equipos: es la vía más común de entrada de virus.\n\n"
                             . "1. Comprueba si el pendrive funciona en otro equipo, para descartar que esté dañado.\n"
                             . "2. Para compartir archivos dentro de la empresa, usa las carpetas de red en vez de un pendrive.\n"
                             . "3. Para archivos que vienen de fuera, pide que te los envíen por correo.\n\n"
                             . "Si tu trabajo requiere usar USB de forma habitual, abre un ticket explicando el motivo. Requiere aprobación de tu jefatura.",
            ],

            // ── Hardware (complemento) ────────────────────────────────
            [
                'categoria' => 'Hardware',
                'title'     => 'El computador está muy lento',
                'symptoms'  => 'computador lento, se demora, tarda en abrir, va lento, equipo pesado',
                'content'   => "1. Presiona Ctrl + Shift + Esc para abrir el Administrador de tareas y mira qué programa consume más CPU o memoria.\n"
                             . "2. Cierra las pestañas del navegador que no estés usando: son las que más memoria consumen.\n"
                             . "3. Reinicia el equipo. Si llevas semanas sin apagarlo, esto solo ya suele resolverlo.\n"
                             . "4. Revisa si hay una actualización de Windows instalándose en segundo plano.\n"
                             . "5. Comprueba el espacio libre del disco: con menos de un 10% libre, todo se vuelve lento.\n\n"
                             . "Si el equipo es lento incluso recién reiniciado y sin programas abiertos, abre un ticket: puede necesitar más memoria.",
            ],
            [
                'categoria' => 'Hardware',
                'title'     => 'El computador se apaga solo',
                'symptoms'  => 'se apaga solo, se reinicia solo, se corta, apagado inesperado, pantallazo azul',
                'content'   => "1. Revisa que el cable de poder esté firme, tanto en el equipo como en el enchufe.\n"
                             . "2. Si es un notebook, comprueba que el cargador esté conectado y que la luz encienda.\n"
                             . "3. Fíjate si el equipo se siente muy caliente o el ventilador suena fuerte antes de apagarse: puede ser sobrecalentamiento.\n"
                             . "4. Revisa que las rejillas de ventilación no estén tapadas con papeles o contra la pared.\n"
                             . "5. Anota si ocurre siempre a la misma hora o al usar cierto programa: ese dato ayuda mucho al diagnóstico.\n\n"
                             . "Abre un ticket indicando desde cuándo ocurre y con qué frecuencia. Guarda tu trabajo seguido mientras tanto.",
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

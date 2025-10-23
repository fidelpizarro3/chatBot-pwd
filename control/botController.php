<?php

/**
 * control/botController.php
 * -------------------------
 * Punto de entrada para BotMan en la aplicación. Maneja:
 *  - Carga de dependencias
 *  - Validaciones auxiliares (fecha, email)
 *  - Configuración de storage y BotMan
 *  - Flujo de solicitud de turno (state machine)
 *  - Integración con MailModel para enviar confirmaciones
 */

//dependecias
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../modelo/botModel.php';
require __DIR__ . '/Storage/FileStorage.php';

//zona horaria
date_default_timezone_set('America/Argentina/Salta');

// Mensajes y plantillas (fallbacks manejados luego)
$messages = require __DIR__ . '/../vista/templates/plantilla_bot.php';

// Validar fecha en formato DD/MM/AAAA y que no sea pasada (al menos mañana)
function validarFechaDdMmYyyyNoPasada(string $texto): ?DateTimeImmutable {
    $t = trim(str_replace(['-', ' '], '/', $texto));
    if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $t)) return null;

    $dt = DateTime::createFromFormat('!d/m/Y', $t);
    $err = DateTime::getLastErrors();
    if ($dt === false || $err['warning_count'] > 0 || $err['error_count'] > 0) return null;

    //validacion fecha valida(no permite 31/02 etc)
    [$dd,$mm,$yy] = array_map('intval', explode('/',$t));
    if (!checkdate($mm,$dd,$yy)) return null;

    //fecha +1 dia actual
    $min = (new DateTimeImmutable('today'))->modify('+1 day'); // mañana 00:00
    $imm = DateTimeImmutable::createFromMutable($dt);
    if ($imm < $min) return null;

    return $imm;
}

//validamos formato de gmail.
function esGmailValido(string $email): bool {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $dominio = substr(strrchr(strtolower($email), '@') ?: '', 1);
    return $dominio === 'gmail.com';
}


//especialidades
$ESPECIALIDADES = [
    'esp:clinica'       => 'Clínica Médica',
    'esp:cardiologia'   => 'Cardiología',
    'esp:odontologia'   => 'Odontología',
    'esp:ginecologia'   => 'Ginecología',
    'esp:traumatologia' => 'Traumatología',
    'esp:dermatologia'  => 'Dermatología',
];


//login y debug
$debugDir = __DIR__ . '/../storage';
if (!is_dir($debugDir)) { @mkdir($debugDir, 0777, true); }
$input = @file_get_contents('php://input');
$log = date('c') . "\nRAW: " . ($input ?: '(empty)') . "\nPOST: " . print_r($_POST, true) . "\nGET: " . print_r($_GET, true) . "\n----\n";
@file_put_contents($debugDir . '/bot_debug.log', $log, FILE_APPEND);

// Si el widget envía JSON lo fusionamos en $_POST para facilitar acceso
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false && $input) {
    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        foreach ($decoded as $k => $v) {
            if (!isset($_POST[$k])) $_POST[$k] = $v;
        }
    }
}


//importamos utilizades del botman
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Control\Storage\FileStorage;
use Modelo\MailModel;

DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

// Si se accede por GET sin parametros de BotMan, servimos la vista del chat
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['botman'])) {
    require __DIR__ . '/../vista/mensaje.php';
    exit;
}


//storage y creacion del bot.
$storageDir = __DIR__ . '/../storage/botman';
$storage = new FileStorage($storageDir);

$config = ['storage' => $storage];
$botman = BotManFactory::create($config);


//creamos instancia de MailModel con config si existe
$mailConfig = [];
if (file_exists(__DIR__ . '/../config/gmail.php')) {
    $mailConfig = require __DIR__ . '/../config/gmail.php';
}
try {
    $mailModel = new MailModel($mailConfig);
} catch (Throwable $e) {
    // Guardamos errores de inicializacion del mailer para diagnostico
    @file_put_contents($debugDir . '/mail_error.log', date('c') . " MailModel init error: " . $e->getMessage() . "\n", FILE_APPEND);
    $mailModel = null;
}

//turno
$botman->hears('.*', function ($bot) use ($storage, $messages, $ESPECIALIDADES, $debugDir, $mailModel) {

    // Identificamos al usuario (puede venir en payload, POST o por el driver)
    $payload = $bot->getMessage()->getPayload() ?: [];
    $userId = $payload['userId'] ?? ($_POST['userId'] ?? ($bot->getUser() ? $bot->getUser()->getId() : null));
    if (!$userId) return; // si no podemos identificar al usuario no seguimos

    // Clave de almacenamiento por usuario
    $key = 'turno_' . $userId;
    $state = $storage->get($key) ?? [];

    // Si no hay flujo activo, no hacemos nada aquí
    if (empty($state) || empty($state['active'])) {
        return;
    }

    $text = trim($bot->getMessage()->getText() ?? '');

    // Avanzamos por pasos segun el estado
    switch ($state['step'] ?? '') {
        case 'awaiting_name':
            // Guardamos nombre y mostramos menu de especialidades
            $state['name'] = $text;
            $state['step'] = 'awaiting_specialty';
            $storage->save($key, $state);

            $q = Question::create($messages['turno_ask_specialty'] ?? "¿Con qué especialidad querés el turno, {$state['name']}?")
                ->addButtons(array_map(function($code) use ($ESPECIALIDADES) {
                    return Button::create($ESPECIALIDADES[$code])->value($code);
                }, array_keys($ESPECIALIDADES)));
            $bot->reply($q);
            break;

        case 'awaiting_specialty':
            // Si viene un value de botón (ej. 'esp:cardiologia') mapeamos a nombre legible
            if (isset($ESPECIALIDADES[$text])) {
                $state['specialty'] = $ESPECIALIDADES[$text];
            } else {
                // Aceptamos texto libre también
                $state['specialty'] = $text;
            }
            $state['step'] = 'awaiting_date';
            $storage->save($key, $state);
            $bot->reply($messages['turno_ask_date'] ?? '🗓️ ¿Para qué fecha necesitás el turno? (DD/MM/AAAA)');
            break;

        case 'awaiting_date':
            // Validamos formato y que la fecha sea al menos maniana
            $dt = validarFechaDdMmYyyyNoPasada($text);
            if (!$dt) {
                $bot->reply($messages['turno_fecha_invalida'] ?? '⚠️ Fecha inválida. Usá DD/MM/AAAA y que sea al menos mañana.');
                $bot->reply($messages['turno_ask_date'] ?? '🗓️ ¿Para qué fecha necesitás el turno? (DD/MM/AAAA)');
                break; // No cambiamos de step
            }

            $state['date'] = $dt->format('d/m/Y');
            $state['step'] = 'awaiting_email';
            $storage->save($key, $state);

            $msgAskEmail = $messages['turno_ask_email'] ?? '📧 Dejanos un email (Gmail) para confirmarte el turno:';
            $bot->reply($msgAskEmail);
            break;

        case 'awaiting_email':
    // Validamos que sea gmail y guardamos, luego terminamos el flujo
    if (!esGmailValido($text)) {
        $bot->reply($messages['turno_email_invalido'] ?? '⚠️ Email inválido. Debe ser una cuenta @gmail.com');
        $bot->reply($messages['turno_ask_email'] ?? '📧 Dejanos un email (Gmail) para confirmarte el turno:');
        break; // NO cambiamos de step
    }

    $state['email'] = trim($text);
    $state['active'] = false; // fin del flujo
    $storage->save($key, $state);

    // Enviamos resumen por mensaje único al chat
    $bot->reply(sprintf(
        $messages['turno_resumen'] ?? "✅ Turno solicitado:\n👤 %s\n🏥 %s\n📅 %s\n📧 %s\nGracias, te confirmamos por correo.",
        $state['name'], $state['specialty'], $state['date'], $state['email']
    ));

    // ---------------------------
    // Envío por email (Laminas Mail)
    // ---------------------------
    if (isset($mailModel) && $mailModel instanceof MailModel) {
        $subject = $messages['turno_mail_subject'] ?? 'Confirmación de turno';

        // HTML bonito
        $htmlBody = '<p>Hola ' . htmlspecialchars($state['name']) . ',</p>';
        $htmlBody .= '<p>Tu turno ha sido registrado con los siguientes datos:</p>';
        $htmlBody .= '<ul>';
        $htmlBody .= '<li><strong>Especialidad:</strong> ' . htmlspecialchars($state['specialty']) . '</li>';
        $htmlBody .= '<li><strong>Fecha:</strong> ' . htmlspecialchars($state['date']) . '</li>';
        $htmlBody .= '</ul>';
        $htmlBody .= '<p>Gracias por confiar en nosotros.</p>';

        // Texto breve para clientes de correo que no soporten HTML
        $textBody = "Hola {$state['name']}, tu turno está confirmado para {$state['date']} en {$state['specialty']}.";

        try {
            $mailModel->send($state['email'], $subject, $htmlBody, $textBody);
        } catch (Throwable $e) {
            @file_put_contents($debugDir . '/mail_error.log', date('c') . " Mail send error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    } else {
        @file_put_contents($debugDir . '/mail_error.log', date('c') . " MailModel not available, skipping send\n", FILE_APPEND);
    }
    break;


        default:
            // Si llega algo raro limpiamos el estado para evitar ciclos
            $storage->delete($key);
            break;
    }

    return;
})->stopsConversation(false);

$botman->hears('^(menu|ayuda|opciones)$', function ($bot) use ($messages) {
    $q = Question::create($messages['menu_question'] ?? '¿Qué querés consultar?')
        ->addButtons([
            Button::create($messages['menu_buttons']['horarios']        ?? '🕒 Horarios')->value('horarios'),
            Button::create($messages['menu_buttons']['ubicacion']       ?? '📍 Ubicación')->value('ubicacion'),
            Button::create($messages['menu_buttons']['contacto']        ?? '📞 Contacto')->value('contacto'),
            Button::create($messages['menu_buttons']['especialidades']  ?? '🏥 Especialidades')->value('especialidades'),
            Button::create($messages['menu_buttons']['obras_sociales']  ?? '💳 Obras sociales')->value('obras sociales'),
            Button::create($messages['menu_buttons']['turno']           ?? '📅 Sacar turno')->value('turno'),
            Button::create($messages['menu_buttons']['humano']          ?? '👤 Hablar con humano')->value('humano'),
        ]);
    $bot->reply($q);
});

// Mostrar menú de especialidades si lo piden explícitamente
$botman->hears('^especialidades$', function ($bot) use ($ESPECIALIDADES) {
    $q = Question::create('Elegí una especialidad:')
        ->addButtons(array_map(function($code) use ($ESPECIALIDADES) {
            return Button::create($ESPECIALIDADES[$code])->value($code);
        }, array_keys($ESPECIALIDADES)));
    $bot->reply($q);
});

// Otras opciones (ancladas)
$botman->hears('^horarios$', fn($bot)=> $bot->reply(BotModel::horarios()));
$botman->hears('^ubicacion$', fn($bot)=> $bot->reply(BotModel::ubicacion()));
$botman->hears('^contacto$', fn($bot)=> $bot->reply(BotModel::hablarConHumano()));
$botman->hears('^obras sociales$', fn($bot)=> $bot->reply(BotModel::obrasSociales()));
$botman->hears('^(humano|hablar con humano|hablar con un humano)$', fn($bot)=> $bot->reply(BotModel::hablarConHumano()));

// Flujo de solicitud de turno
$botman->hears('^(?i)turno$', function ($bot) use ($storage, $messages) {
    $payload = $bot->getMessage()->getPayload() ?: [];
    $userId = $payload['userId'] ?? ($_POST['userId'] ?? ($bot->getUser() ? $bot->getUser()->getId() : null));
    if (!$userId) {
        $bot->reply($messages['user_not_identified'] ?? 'No pude identificarte. Probá de nuevo.');
        return;
    }
    $key = 'turno_' . $userId;
    $state = ['active' => true, 'step' => 'awaiting_name'];
    $storage->save($key, $state);

    $bot->reply($messages['turno_ask_name'] ?? '👋 ¿Cuál es tu nombre completo?');
    $bot->reply($messages['turno_ask_name_2'] ?? 'Escribilo en un solo mensaje 🙂');
});

// Fallback
$botman->fallback(function ($bot) use ($messages) {
    $bot->reply($messages['fallback'] ?? "No entendí 🤔. Escribí *menu* o tocá una opción.");
});



$botman->listen();

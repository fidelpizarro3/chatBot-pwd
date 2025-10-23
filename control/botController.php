<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../modelo/botModel.php';
require __DIR__ . '/Conversaciones/turno.php';
require __DIR__ . '/Storage/FileStorage.php';

$debugDir = __DIR__ . '/../storage';
if (!is_dir($debugDir)) {
    @mkdir($debugDir, 0777, true);
}
$input = @file_get_contents('php://input');
$log = date('c') . "\nRAW: " . ($input ?: '(empty)') . "\nPOST: " . print_r($_POST, true) . "\nGET: " . print_r($_GET, true) . "\nSERVER: " . print_r(array_intersect_key($_SERVER, array_flip(['REQUEST_METHOD','CONTENT_TYPE','HTTP_USER_AGENT','REMOTE_ADDR'])), true) . "\n----\n";
@file_put_contents($debugDir . '/bot_debug.log', $log, FILE_APPEND);

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false && $input) {
    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        foreach ($decoded as $k => $v) {
            if (!isset($_POST[$k])) {
                $_POST[$k] = $v;
            }
        }
    }
}

use Conversaciones\Turno;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use Control\Storage\FileStorage;


DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['botman'])) {
    require __DIR__ . '/../vista/mensaje.php';
    exit;
}


$storageDir = __DIR__ . '/../storage/botman';
$storage = new FileStorage($storageDir);

$config = [
    'storage' => $storage,
];

$botman = BotManFactory::create($config);

$botman->hears('.*', function ($bot) use ($storage) {
    $payload = $bot->getMessage()->getPayload() ?: [];
    $userId = $payload['userId'] ?? ($bot->getUser() ? $bot->getUser()->getId() : null);
    if (!$userId) {
        return;
    }

    $key = 'turno_' . $userId;
    $state = $storage->get($key) ?? [];

    if (empty($state) || empty($state['active'])) {
        return;
    }

    $text = trim($bot->getMessage()->getText() ?? '');

    switch ($state['step'] ?? '') {
        case 'awaiting_name':
            $state['name'] = $text;
            $state['step'] = 'awaiting_specialty';
            $storage->save($key, $state);
            $bot->reply("Perfecto, {$state['name']}. ¿Con qué especialidad o médico querés el turno?");
            break;
        case 'awaiting_specialty':
            $state['specialty'] = $text;
            $state['step'] = 'awaiting_date';
            $storage->save($key, $state);
            $bot->reply('¿Para qué fecha necesitás el turno? (DD/MM/AAAA)');
            break;
        case 'awaiting_date':
            $state['date'] = $text;
            $state['step'] = 'awaiting_email';
            $storage->save($key, $state);
            $bot->reply('Dejanos un email para confirmarte el turno:');
            break;
        case 'awaiting_email':
            $state['email'] = $text;
            $state['active'] = false;
            $storage->save($key, $state);
            // resumen
            $resumen = "✅ Turno solicitado:\n👤 Nombre: {$state['name']}\n🏥 Especialidad: {$state['specialty']}\n📅 Fecha: {$state['date']}\n📧 Email: {$state['email']}";
            $bot->reply($resumen);
            $bot->reply("Gracias {$state['name']}, te contactaremos para confirmar.");
            break;
        default:
            $storage->delete($key);
            break;
    }

    return;
})->stopsConversation(false);

$botman->hears('hola', function ($bot) {
    $bot->reply(BotModel::saludar());
});

$botman->hears('adios', function ($bot) {
    $bot->reply(BotModel::despedir());
});

$botman->hears('mi nombre es {nombre}', function ($bot, $nombre) {
    $bot->reply(BotModel::presentarse($nombre));
});

$botman->hears('menu|ayuda|opciones', function ($bot) {
    $bot->reply(
        Question::create('¿Qué querés consultar?')
            ->addButtons([
                Button::create('🕒 Horarios')->value('horarios'),
                Button::create('📍 Ubicación')->value('ubicacion'),
                Button::create('📞 Contacto')->value('contacto'),
                Button::create('🏥 Especialidades')->value('especialidades'),
                Button::create('💳 Obras sociales')->value('obras sociales'),
                Button::create('📅 Sacar turno')->value('turno'),
                Button::create('👤 Hablar con humano')->value('humano'),
            ])
    );
});

$botman->hears('^horarios$', fn($bot)=> $bot->reply(BotModel::horarios()));
$botman->hears('^ubicacion$', fn($bot)=> $bot->reply(BotModel::ubicacion()));
$botman->hears('^contacto$', fn($bot)=> $bot->reply(BotModel::hablarConHumano()));
$botman->hears('^especialidades?$', fn($bot)=> $bot->reply(BotModel::especialidades()));
$botman->hears('^obras sociales$', fn($bot)=> $bot->reply(BotModel::obrasSociales()));
$botman->hears('^(humano|hablar con humano|hablar con un humano)$', fn($bot)=> $bot->reply(BotModel::hablarConHumano()));

$botman->hears('horarios', function ($bot) {
    $bot->reply(BotModel::horarios());
});

$botman->hears('ubicacion', function ($bot) {
    $bot->reply(BotModel::ubicacion());
});

$botman->hears('especialidades', function ($bot) {
    $bot->reply(BotModel::especialidades());
});

$botman->hears('obras sociales', function ($bot) {
    $bot->reply(BotModel::obrasSociales());
});

$botman->hears('hablar con un humano', function ($bot) {
    $bot->reply(BotModel::hablarConHumano());
});

$botman->hears('contacto', function ($bot) {
    $bot->reply(BotModel::hablarConHumano());
});

$botman->fallback(function ($bot) {
    $bot->reply("No entendí 🤔. Escribí *menu* o tocá una opción.");
});

$botman->hears('(?i).*\bturno\b.*', function ($bot) use ($storage) {

    $payload = $bot->getMessage()->getPayload() ?: [];
    $userId = $payload['userId'] ?? $_POST['userId'] ?? ($bot->getUser() ? $bot->getUser()->getId() : null);
    if (!$userId) {
        $bot->reply('No pude identificar tu usuario, intentá nuevamente.');
        return;
    }

    $key = 'turno_' . $userId;
    $state = [
        'active' => true,
        'step' => 'awaiting_name',
    ];
    $storage->save($key, $state);

    $bot->reply('👋 Vamos a sacar un turno.');
    $bot->reply('¿Cuál es tu nombre completo?');
    return;
});

$botman->hears('.*', function ($bot) {
    error_log('LLEGA: ' . $bot->getMessage()->getText());
})->stopsConversation(false);

$botman->listen();

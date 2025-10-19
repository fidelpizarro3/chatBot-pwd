<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../modelo/botModel.php';

use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;


// 1) Cargar driver Web
DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);

// 2) Mostrar la vista SOLO si vienen por GET y no es el widget
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['botman'])) {
    require __DIR__ . '/../vista/mensaje.php';
    exit;
}

// 3) Instancia del bot
$config = [];
$botman = BotManFactory::create($config);

// 4) Reglas
$botman->hears('hola', function ($bot) {
    $bot->reply(BotModel::saludar());
});

$botman->hears('adios', function ($bot) {
    $bot->reply(BotModel::despedir());
});

$botman->hears('mi nombre es {nombre}', function ($bot, $nombre) {
    $bot->reply(BotModel::presentarse($nombre));
});



$botman->hears('menu', function ($bot) {
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

$botman->hears('sacar turno', function ($bot) {
    $bot->reply(BotModel::sacarTurno());
});

$botman->hears('contacto', function ($bot) {
    $bot->reply(BotModel::hablarConHumano());
});

$botman->fallback(function ($bot) {
    $bot->reply(BotModel::fallback());
});


// 6) Escuchar ESTA petición (POST del widget)
$botman->listen();

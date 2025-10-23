<?php

use Modelo\MailModel;

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/gmail.php';

$mailModel = new MailModel($config);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    $nombre = $_POST['nombre'] ?? 'Usuario';
    $emailDestino = $_POST['email'] ?? 'destinatario@example.com';

    $asunto = "Hola, $nombre — tu consulta";
    $html   = "<h1>Gracias por contactarnos, $nombre</h1><p>Te responderemos a la brevedad.</p>";
    $texto  = "Gracias por contactarnos, $nombre. Te responderemos a la brevedad.";

    $mailModel->send(
        toEmail: $emailDestino,
        subject: $asunto,
        htmlBody: $html,
        textBody: $texto,
        attachments: []
    );

    header('Location: /vista/mensaje.php?ok=1');
    exit;
} catch (\Throwable $e) {
    error_log('MAIL ERROR: '.$e->getMessage());
    header('Location: /vista/mensaje.php?ok=0&err='.urlencode('No se pudo enviar el mail'));
    exit;
}
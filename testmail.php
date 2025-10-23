<?php
require __DIR__ . '/vendor/autoload.php';

use Modelo\MailModel;

$config = require __DIR__ . '/config/gmail.php';
$mail   = new MailModel($config);

try {
$mail->send(
    'gastonn.okk@gmail.com',
    '✅ Prueba Laminas Mail',
    '<h1>Funciona!</h1><p>Correo HTML</p>',
    'hola majo <3!'
);
    echo "✅ Correo enviado correctamente.";
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage();
}
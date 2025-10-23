<?php
require __DIR__ . '/../vendor/autoload.php';

use Google\Client;
use Google\Service\Calendar;

$client = new Client();
$client->setApplicationName('ChatBot Calendar');

// 🔥 Scope corregido
$client->setScopes(['https://www.googleapis.com/auth/calendar']);

$client->setAuthConfig(__DIR__ . '/../config/credentials.json');
$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// 🔥 Importante: redirect_uri coincide con el JSON
$client->setRedirectUri('http://localhost');

$tokenPath = __DIR__ . '/../config/token.json';

// Si ya hay token
if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);
}

// Si expira o no existe
if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    } else {
        $authUrl = $client->createAuthUrl();
        echo "🔗 Abrí este enlace en tu navegador:\n$authUrl\n";
        echo "\nPegá el código de verificación aquí: ";
        $authCode = trim(fgets(STDIN));

        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        $client->setAccessToken($accessToken);

        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        echo "\n✅ Token guardado en: config/token.json\n";
    }
}

echo "✅ Autenticación completada correctamente.\n";

<?php
use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;

class MailModel {

    public static function pruebaEnvio($destinatario, $asunto, $cuerpo) {
        // 1. Validaciones básicas
        $dest = trim($destinatario);
        $subj = trim($asunto);
        $msg  = trim($cuerpo);

        if ($dest === '' || $subj === '' || $msg === '') {
            return "⚠️ Faltan datos para enviar el mail.";
        }

        if (!filter_var($dest, FILTER_VALIDATE_EMAIL)) {
            return "⚠️ El destinatario no parece un email válido.";
        }

        try {
            // 2. Armar contenido MIME (HTML)
            $html = new MimePart($msg);
            $html->type = "text/html";
            $body = new MimeMessage();
            $body->addPart($html);

            // 3. Crear mensaje
            $mail = new Message();
            $mail->setEncoding('UTF-8')
                    ->addTo($dest)
                    ->addFrom('tucorreo@gmail.com', 'Asistente PHP')
                    ->setSubject($subj)
                    ->setBody($body);

            // 4. Configurar transporte SMTP (por ejemplo Gmail)
            $transport = new Smtp();
            $options = new SmtpOptions([
                'name' => 'smtp.gmail.com',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'connection_class' => 'login',
                'connection_config' => [
                    'username' => 'tucorreo@gmail.com',
                    'password' => 'tu_app_password', // no la contraseña real
                    'ssl' => 'tls',
                ],
            ]);
            $transport->setOptions($options);

            // 5. Enviar
            $transport->send($mail);

            return "✅ Correo enviado correctamente a $dest.";
        } catch (Throwable $e) {
            return "❌ Error al enviar: " . $e->getMessage();
        }
    }
}

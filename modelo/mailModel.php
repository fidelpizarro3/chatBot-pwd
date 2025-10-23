<?php
namespace Modelo;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;

/**
 * MailModel
 * ---------
 * Pequeña envoltura para enviar correos usando Laminas\Mail (SMTP).
 * - Configuración esperada: [ 'smtp' => [ 'host','port','connection_class','connection_config', 'from' => ['email','name'] ] ]
 */
class MailModel
{
    private Smtp $transport;
    private array $defaultFrom;

    /**
     * Constructor
     * @param array $config Configuración de SMTP (ver arriba). Si faltan valores se usan valores por defecto razonables.
     */
    public function __construct(array $config)
    {
        $options = new SmtpOptions([
            'name'              => $config['smtp']['name'] ?? 'smtp.gmail.com',
            'host'              => $config['smtp']['host'] ?? 'smtp.gmail.com',
            'port'              => $config['smtp']['port'] ?? 587,
            'connection_class'  => $config['smtp']['connection_class'] ?? 'login',
            'connection_config' => $config['smtp']['connection_config'] ?? [],
        ]);

        $this->transport   = new Smtp($options);
        $this->defaultFrom = $config['smtp']['from'] ?? [];
    }

    /**
     * send
     * Envia un correo al destinatario indicado. Soporta cuerpo en HTML y texto, y adjuntos opcionales.
     *
     * @param string $toEmail     Email del destinatario
     * @param string $subject     Asunto
     * @param string|null $htmlBody  Cuerpo en HTML (opcional)
     * @param string|null $textBody  Cuerpo en texto plano (opcional)
     * @param array $attachments      Rutas a archivos a adjuntar (opcional)
     * @param array|null $replyTo     Array con reply-to opcional (['email'=>'x','name'=>'y'])
     * @return void
     */
    public function send(
        string $toEmail,
        string $subject,
        ?string $htmlBody = null,
        ?string $textBody = null,
        array $attachments = [],
        ?array $replyTo = null
    ): void {
        $message = new Message();

        // From: si està definido en config lo usamos, sino un fallback
        if (!empty($this->defaultFrom['email'])) {
            $message->addFrom(
                $this->defaultFrom['email'],
                $this->defaultFrom['name'] ?? null
            );
        } else {
            $message->addFrom('no-reply@local', 'No Reply');
        }

        // To + asunto + encoding
        $message->addTo($toEmail)
                ->setSubject($subject)
                ->setEncoding('UTF-8');

        // Construimos los diferentes partes MIME (texto, html, adjuntos)
        $parts = [];

        if ($textBody !== null) {
            $textPart = new MimePart($textBody);
            $textPart->type     = 'text/plain; charset=UTF-8';
            $textPart->encoding = 'quoted-printable';
            $parts[] = $textPart;
        }

        if ($htmlBody !== null) {
            $htmlPart = new MimePart($htmlBody);
            $htmlPart->type     = 'text/html; charset=UTF-8';
            $htmlPart->encoding = 'quoted-printable';
            $parts[] = $htmlPart;
        }

        // Adjuntos: añadimos cada archivo si existe y es legible
        foreach ($attachments as $path) {
            if (!is_string($path) || !is_readable($path)) {
                continue;
            }
            $fileContent = file_get_contents($path);
            if ($fileContent === false) {
                continue;
            }

            $filePart = new MimePart($fileContent);
            $filePart->type        = mime_content_type($path) ?: 'application/octet-stream';
            $filePart->disposition = 'attachment';
            $filePart->encoding    = 'base64';
            $filePart->filename    = basename($path);
            $parts[] = $filePart;
        }

        // Si no hay partes (caso improbable) añadimos un fallback en texto
        if (empty($parts)) {
            $fallback = new MimePart('Mensaje sin contenido');
            $fallback->type     = 'text/plain; charset=UTF-8';
            $fallback->encoding = 'quoted-printable';
            $parts[] = $fallback;
        }

        // Montamos el cuerpo MIME y enviamos
        $body = new MimeMessage();
        $body->setParts($parts);
        $message->setBody($body);

        $this->transport->send($message);
    }
}
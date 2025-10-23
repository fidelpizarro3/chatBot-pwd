<?php
namespace Modelo;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;

class MailModel
{
    private Smtp $transport;
    private array $defaultFrom;

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
     *
     *
     * @param string
     * @param string
     * @param string|null
     * @param string|null
     * @param array      
     * @param array|null 
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

        
        if (!empty($this->defaultFrom['email'])) {
            $message->addFrom(
                $this->defaultFrom['email'],
                $this->defaultFrom['name'] ?? null
            );
        } else {
            $message->addFrom('no-reply@local', 'No Reply');
        }

        $message->addTo($toEmail)
                ->setSubject($subject)
                ->setEncoding('UTF-8');

        
        $parts = [];

        if ($textBody !== null) {
            $textPart = new MimePart($textBody);
            $textPart->type     = 'text/plain; charset=UTF-8';
            $textPart->encoding = 'quoted-printable';
            $parts[] = $textPart;
        }

        if ($htmlBody !== null) { //cuerpo en HTML
            $htmlPart = new MimePart($htmlBody);
            $htmlPart->type     = 'text/html; charset=UTF-8';
            $htmlPart->encoding = 'quoted-printable';
            $parts[] = $htmlPart;
        }

        // Adjuntos
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

        if (empty($parts)) {
            $fallback = new MimePart('Mensaje sin contenido');
            $fallback->type     = 'text/plain; charset=UTF-8';
            $fallback->encoding = 'quoted-printable';
            $parts[] = $fallback;
        }

        $body = new MimeMessage(); 
        $body->setParts($parts);
        $message->setBody($body);

        $this->transport->send($message);
    }
}
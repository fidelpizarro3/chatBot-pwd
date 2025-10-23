<?php

namespace Conversaciones;

use BotMan\BotMan\Messages\Conversations\Conversation;
use Modelo\MailModel;

class Turno extends Conversation
{
    protected string $nombre;
    protected string $especialidad;
    protected string $fecha;
    protected string $email = '';

    public function run()
    {
        $this->preguntarNombre();
    }

    public function preguntarNombre()
    {
        $this->say('👋 Vamos a sacar un turno.');
        $this->ask('¿Cuál es tu *nombre completo*?', function ($answer) {
            $this->nombre = trim($answer->getText());
            $this->preguntarEspecialidad();
        });
    }

    public function preguntarEspecialidad()
    {
        $this->ask("Perfecto, {$this->nombre}. ¿Con qué *especialidad o médico* querés el turno?", function ($answer) {
            $this->especialidad = trim($answer->getText());
            $this->preguntarFecha();
        });
    }

public function preguntarFecha()
{
    $this->ask('🗓️ ¿Para qué fecha necesitás el turno? *(DD/MM/AAAA)*', function ($answer) {

        $texto = trim($answer->getText());
        $texto = str_replace(['-', ' '], '/', $texto);

        // 1) Formato exacto DD/MM/AAAA
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $texto)) {
            $this->say('⚠️ Formato inválido. Usá **DD/MM/AAAA** (ej: 25/10/2025).');
            return $this->preguntarFecha(); 
        }

        // 2) Parse robusto y sin “autocorrecciones”
        $dt = \DateTime::createFromFormat('!d/m/Y', $texto);
        $errors = \DateTime::getLastErrors();
        if ($dt === false || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
            $this->say('⚠️ La fecha no es válida. Probá otra vez.');
            return $this->preguntarFecha();
        }

        [$dd, $mm, $yyyy] = array_map('intval', explode('/', $texto));
        if (!checkdate($mm, $dd, $yyyy)) {
            $this->say('⚠️ La fecha no existe. Probá otra vez.');
            return $this->preguntarFecha();
        }

        $hoy      = new \DateTimeImmutable('today'); 
        $maniana  = $hoy->modify('+1 day');   
        $dtImm    = \DateTimeImmutable::createFromMutable($dt); 

        if ($dtImm < $maniana) {
            $this->say('⚠️ La fecha debe ser **al menos mañana** (' . $maniana->format('d/m/Y') . ').');
            return $this->preguntarFecha();
        }


        $this->fecha = $dtImm->format('d/m/Y');

        // Continuar
        return $this->preguntarEmail(); 
    });
}

    public function preguntarEmail()
    {
        $this->ask('Dejanos un *email* para confirmarte el turno:', function ($answer) {
            $email = trim($answer->getText());
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->say('Email inválido. Probá de nuevo (ej: paciente@mail.com).');
                return $this->preguntarEmail();
            }
            $this->email = $email;
            $this->confirmarTurno();
        });
    }

    public function confirmarTurno()
    {
        $this->typing(1);
        $resumen = "✅ *Turno solicitado*\n"
            . "👤 *Nombre:* {$this->nombre}\n"
            . "🏥 *Especialidad:* {$this->especialidad}\n"
            . "📅 *Fecha:* {$this->fecha}\n"
            . "📧 *Email:* {$this->email}";

        $this->say($resumen);
        $this->say("¡Gracias, {$this->nombre}! Te enviaremos confirmación por correo.");

        try {
            $config = require __DIR__ . '/../../config/gmail.php';
            $mailer = new MailModel($config);

            $mailer->send(
                'turnosclinicaunco@gmail.com',
                'Nuevo turno solicitado',
                "<p><b>Nombre:</b> {$this->nombre}<br><b>Especialidad:</b> {$this->especialidad}<br><b>Fecha:</b> {$this->fecha}<br><b>Email:</b> {$this->email}</p>",
                "Turno: {$this->nombre} - {$this->especialidad} - {$this->fecha} - {$this->email}"
            );

            // Envío de confirmación al paciente
            $mailer->send(
                $this->email,
                'Solicitud de turno recibida',
                "<p>Hola {$this->nombre}, recibimos tu solicitud para <b>{$this->especialidad}</b> el <b>{$this->fecha}</b>.<br>Te contactaremos para confirmarla.</p>",
                "Hola {$this->nombre}, recibimos tu solicitud de turno. Te contactaremos para confirmarla."
            );

        } catch (\Throwable $e) {
            $this->say("Hubo un error al enviar el correo: " . $e->getMessage());
        }
    }

}
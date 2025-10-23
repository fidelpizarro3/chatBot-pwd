<?php
return [
    'bienvenida' => '👋 Vamos a sacar un turno.',
    'pedir_nombre' => '¿Cuál es tu *nombre completo*?',
    'pedir_especialidad' => 'Perfecto, %s. ¿Con qué *especialidad o médico* querés el turno?',
    'pedir_fecha' => '🗓️ Elegí una fecha:',
    'fecha_invalida' => '⚠️ La fecha no es válida. Probá otra.',
    'pedir_email' => 'Dejanos un *email* para confirmarte el turno:',
    'email_invalido' => 'Email inválido. Probá de nuevo (ej: paciente@mail.com).',
    'resumen_turno' => "✅ *Turno solicitado*\n👤 *Nombre:* %s\n🏥 *Especialidad:* %s\n📅 *Fecha:* %s\n📧 *Email:* %s",
    'gracias' => '¡Gracias, %s! Te enviaremos confirmación por correo.',
    'error_email' => 'Hubo un error al enviar el correo: %s',
    'email_admin_subject' => 'Nuevo turno solicitado',
    'email_admin_html' => '<p><b>Nombre:</b> %s<br><b>Especialidad:</b> %s<br><b>Fecha:</b> %s<br><b>Email:</b> %s</p>',
    'email_admin_text' => 'Turno: %s - %s - %s - %s',
    'email_paciente_subject' => 'Solicitud de turno recibida',
    'email_paciente_html' => '<p>Hola %s, recibimos tu solicitud para <b>%s</b> el <b>%s</b>.<br>Te contactaremos para confirmarla.</p>',
    'email_paciente_text' => 'Hola %s, recibimos tu solicitud de turno. Te contactaremos para confirmarla.'
];
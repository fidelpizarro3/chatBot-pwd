<?php
return [
    // Mensajes de sistema
    'error_usuario' => 'No pude identificar tu usuario, intentá nuevamente.',
    'error_comando' => 'No entendí 🤔. Escribí *menu* o tocá una opción.',

    // Menú principal
    'pregunta_menu' => '¿Qué querés consultar?',
    'botones_menu' => [
        'horarios' => '🕒 Horarios',
        'ubicacion' => '📍 Ubicación',
        'contacto' => '📞 Contacto',
        'especialidades' => '🏥 Especialidades',
        'obras_sociales' => '💳 Obras sociales',
        'turno' => '📅 Sacar turno',
        'humano' => '👤 Hablar con humano',
    ],

    // Flujo de turno
    'inicio_turno' => '👋 Vamos a sacar un turno.',
    'pedir_nombre' => '¿Cuál es tu nombre completo?',
    'pedir_especialidad' => 'Perfecto, %s. ¿Con qué especialidad o médico querés el turno?',
    'pedir_fecha' => '¿Para qué fecha necesitás el turno? (DD/MM/AAAA)',
    'fecha_invalida' => '⚠️ La fecha no es válida. Probá otra (DD/MM/AAAA) y que sea al menos mañana.',
    'pedir_email' => 'Dejanos un email para confirmarte el turno:',
    'mostrar_resumen' => "✅ Turno solicitado:\n👤 Nombre: %s\n🏥 Especialidad: %s\n📅 Fecha: %s\n📧 Email: %s",
    'mensaje_gracias' => 'Gracias %s, te contactaremos para confirmar.',
];
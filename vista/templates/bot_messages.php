<?php
return [
    // Mensajes de sistema
    'user_not_identified' => 'No pude identificar tu usuario, intentá nuevamente.',
    'fallback' => 'No entendí 🤔. Escribí *menu* o tocá una opción.',

    // Menú principal
    'menu_question' => '¿Qué querés consultar?',
    'menu_buttons' => [
        'horarios' => '🕒 Horarios',
        'ubicacion' => '📍 Ubicación',
        'contacto' => '📞 Contacto',
        'especialidades' => '🏥 Especialidades',
        'obras_sociales' => '💳 Obras sociales',
        'turno' => '📅 Sacar turno',
        'humano' => '👤 Hablar con humano',
    ],

    // Flujo de turno (en botController)
    'turno_ask_name' => '👋 Vamos a sacar un turno.',
    'turno_ask_name_2' => '¿Cuál es tu nombre completo?',
    'turno_ask_specialty' => 'Perfecto, %s. ¿Con qué especialidad o médico querés el turno?',
    'turno_ask_date' => '¿Para qué fecha necesitás el turno? (DD/MM/AAAA)',
    'turno_ask_email' => 'Dejanos un email para confirmarte el turno:',
    'turno_resumen' => "✅ Turno solicitado:\n👤 Nombre: %s\n🏥 Especialidad: %s\n📅 Fecha: %s\n📧 Email: %s",
    'turno_gracias' => 'Gracias %s, te contactaremos para confirmar.',
];
<?php
class BotModel {

    public static function saludar() {
        $mensaje = "¡Hola! ¿Cómo estás?";
        return $mensaje;
    }

    public static function despedir() {
        $mensaje = "¡Hasta luego!";
        return $mensaje;
    }

    public static function presentarse($nombre) {
        $nombreLimpio = trim($nombre);
        $mensaje = "Encantado de conocerte, " . $nombreLimpio . ".";
        return $mensaje;
    }

    public static function fallback() {
        $mensaje = "No entendí eso 🤔. Probá con 'menu', 'horarios', 'ubicacion', 'especialidades', 'obras sociales' o 'contacto'.";
        return $mensaje;
    }

    
    public static function menu() {
        $mensaje = "Opciones:\n- horarios\n
        - ubicacion\n
        - contacto\n
        - especialidades\n
        - obras sociales\n
        - sacar turno\n 
        - hablar con un humano
        \n(Escribí una palabra de la lista)";
        return $mensaje;
    }

    public static function horarios() {
        $mensaje = "Horarios: Lunes a Viernes de 8:00 a 18:00. Sábados de 9:00 a 12:00.";
        return $mensaje;
    }

    public static function ubicacion() {
        $mensaje = "Ubicación: Av. Siempre Viva 742, Neuquén (a 2 cuadras de la plaza).";
        return $mensaje;
    }
    
    public static function especialidades() {
        $mensaje = "Especialidades:\n- Clínica Médica\n- Pediatría\n- Ginecología\n- Cardiología\n- Dermatología";
        return $mensaje;
    }

        public static function obrasSociales() {
        $mensaje = "Obras sociales:\n- OSDE\n- Swiss Medical\n- PAMI\n- IOMA\n- Medicus\n(Consultá por otras).";
        return $mensaje;
    }

    public static function hablarConHumano() {
        $mensaje = "SOLO LLAMADAS! Contacto: Teléfono (299) 123-4567, Email: contacto@ejemplo.com";
        return $mensaje;
    }

    public static function sacarTurno() {
        $mensaje = "Para sacar un turno, por favor llamá al (299) 123-4567 o visitá nuestra página web www.ejemplo.com/turnos.";
        return $mensaje;
    }

    public static function contacto() {

        $mensaje = "Tel: 299-123456 • WhatsApp: 299-555555 • Mail: recepcion@consultorio.com";
        return $mensaje;
    }
}
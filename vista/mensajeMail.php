<?php
$ok  = (int)($_GET['ok'] ?? 0);
$err = $_GET['err'] ?? '';
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><title>Estado del envío</title></head>
<body>
<?php if ($ok): ?>
    <h2>✅ Mensaje enviado correctamente</h2>
<?php else: ?>
    <h2>❌ Error al enviar</h2>
    <?php if ($err): ?><p><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php endif; ?>
</body>
</html>
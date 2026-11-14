<?php
// registro.php — Registro de nuevos pacientes.
require_once __DIR__ . '/includes/bootstrap.php';
if ($u = usuario_actual()) redirect(inicio_por_rol($u['rol']));

$errores = [];
$v = ['nombre' => '', 'usuario' => '', 'telefono' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v['nombre']  = trim($_POST['nombre'] ?? '');
    $v['usuario'] = strtolower(trim($_POST['usuario'] ?? ''));
    $v['telefono'] = trim($_POST['telefono'] ?? '');
    $pass  = $_POST['pass'] ?? '';
    $pass2 = $_POST['pass2'] ?? '';

    if (mb_strlen($v['nombre']) < 5) $errores[] = 'Escriba su nombre completo.';
    if (!preg_match('/^[a-z0-9_.]{4,20}$/', $v['usuario'])) $errores[] = 'El usuario debe tener de 4 a 20 caracteres (letras, números, punto o guion bajo).';
    if (strlen($pass) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($v['telefono'] !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $v['telefono'])) $errores[] = 'El teléfono no es válido.';
    if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';
    if (!$errores) {
        $st = $pdo->prepare("SELECT 1 FROM usuarios WHERE usuario=?");
        $st->execute([$v['usuario']]);
        if ($st->fetch()) $errores[] = 'Ese usuario ya existe, elija otro.';
    }
    if (!$errores) {
        $pdo->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, creado, telefono) VALUES (?,?,?,'paciente',?,?)")
            ->execute([$v['nombre'], $v['usuario'], password_hash($pass, PASSWORD_DEFAULT), date('Y-m-d H:i:s'), $v['telefono']]);
        flash('ok', 'Cuenta creada. Ya puede iniciar sesión.');
        redirect('login.php');
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Crear cuenta · MediCitas</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="auth-body">
<div class="auth single">
    <section class="auth-card">
        <h2>Crear cuenta de paciente</h2>
        <p class="muted">Con su cuenta podrá agendar y revisar sus citas.</p>
        <?php foreach ($errores as $er): ?><div class="alert a-error"><?= e($er) ?></div><?php endforeach; ?>
        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>
            <label>Nombre completo
                <input type="text" name="nombre" value="<?= e($v['nombre']) ?>" required>
            </label>
            <label>Usuario
                <input type="text" name="usuario" value="<?= e($v['usuario']) ?>" required>
            </label>
            <label>Teléfono (opcional, para recordarle sus citas)
                <input type="text" name="telefono" value="<?= e($v['telefono']) ?>" placeholder="0990000000">
            </label>
            <div class="grid2">
                <label>Contraseña<input type="password" name="pass" required></label>
                <label>Repetir contraseña<input type="password" name="pass2" required></label>
            </div>
            <button class="btn" type="submit">Crear cuenta</button>
        </form>
        <p class="center"><a href="<?= url('login.php') ?>">← Volver a iniciar sesión</a></p>
    </section>
</div>
</body>
</html>

<?php
// login.php — Inicio de sesión para administradores y pacientes.
require_once __DIR__ . '/includes/bootstrap.php';
if ($u = usuario_actual()) redirect(inicio_por_rol($u['rol']));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $st->execute([trim($_POST['user'] ?? '')]);
    $row = $st->fetch();
    if ($row && password_verify($_POST['pass'] ?? '', $row['password'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $row['id'];
        redirect(inicio_por_rol($row['rol']));
    }
    $error = 'Acceso denegado. Verifique su usuario y contraseña.';
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar · MediCitas</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body class="auth-body">
<div class="auth">
    <aside class="auth-side">
        <div class="logo big">✚</div>
        <h1>MediCitas</h1>
        <p>Sistema web de citas médicas portable. Agende, confirme y controle las atenciones desde un solo lugar.</p>
        <ul>
            <li>Pacientes: agendan y siguen sus citas</li>
            <li>Administración: gestiona citas y usuarios</li>
            <li>Funciona sin servidor externo (SQLite)</li>
        </ul>
    </aside>
    <section class="auth-card">
        <h2>Iniciar sesión</h2>
        <p class="muted">Ingrese con su cuenta para continuar.</p>
        <?php if ($error): ?><div class="alert a-error"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" autocomplete="off">
            <?= csrf_field() ?>
            <label>Usuario
                <input type="text" name="user" placeholder="admin" required autofocus>
            </label>
            <label>Contraseña
                <input type="password" name="pass" placeholder="••••" required>
            </label>
            <button class="btn" type="submit">Ingresar</button>
        </form>
        <p class="center">¿Eres paciente nuevo? <a href="<?= url('registro.php') ?>">Crear cuenta</a></p>
        <p class="center"><a href="<?= url('') ?>">← Volver al inicio</a></p>
        <div class="demo">
            <strong>Cuentas de prueba</strong>
            <span>Administrador: <code>admin</code> / <code>1234</code></span>
            <span>Paciente: <code>paciente</code> / <code>1234</code></span>
        </div>
    </section>
</div>
</body>
</html>

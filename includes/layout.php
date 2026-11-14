<?php
// includes/layout.php — Cabecera y pie comunes (plantilla visual).
function pagina_inicio(string $titulo, string $activo = ''): void {
    $u = usuario_actual();
    $menu = [];
    if ($u && $u['rol'] === 'admin') {
        $menu = ['panel' => ['Panel', 'admin/index.php'], 'citas' => ['Citas', 'admin/citas.php'],
                 'medicos' => ['Médicos', 'admin/medicos.php'], 'usuarios' => ['Usuarios', 'admin/usuarios.php'], 'nueva' => ['Nueva cita', 'nueva_cita.php']];
    } elseif ($u) {
        $menu = ['mis' => ['Mis citas', 'paciente/index.php'], 'dir' => ['Nuestros médicos', 'medicos.php'], 'nueva' => ['Agendar cita', 'nueva_cita.php']];
    }
    ?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> · MediCitas</title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
<?php if ($u): ?>
<header class="topbar">
    <div class="topbar-in">
        <a class="brand" href="<?= url(inicio_por_rol($u['rol'])) ?>"><span class="logo">✚</span> MediCitas</a>
        <nav>
            <?php foreach ($menu as $k => [$txt, $href]): ?>
                <a href="<?= url($href) ?>" class="<?= $k === $activo ? 'on' : '' ?>"><?= e($txt) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="who">
            <span class="chip"><?= e($u['nombre']) ?> · <?= $u['rol'] === 'admin' ? 'Administrador' : 'Paciente' ?></span>
            <a class="logout" href="<?= url('logout.php') ?>">Salir</a>
        </div>
    </div>
</header>
<?php endif; ?>
<main class="wrap">
<?php foreach (flashes() as [$t, $m]): ?>
    <div class="alert a-<?= e($t) ?>"><?= e($m) ?></div>
<?php endforeach;
}

function pagina_fin(): void { ?>
</main>
<footer class="foot">Sistema Web de Citas Médicas Portable · PHP + SQLite · UNEMI – Interacción Humano-Computador</footer>
</body>
</html>
<?php }

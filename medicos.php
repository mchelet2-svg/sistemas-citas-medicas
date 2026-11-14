<?php
// medicos.php — Directorio de médicos (visible para pacientes y administradores).
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
requiere_login();

$medicos = $pdo->query("SELECT * FROM medicos WHERE activo=1 ORDER BY especialidad, nombre")->fetchAll();
pagina_inicio('Nuestros médicos', 'dir');
?>
<div class="page-head"><h1>Nuestros médicos</h1><p class="muted">Conozca al equipo y agende directamente con el especialista que necesite.</p></div>
<div class="docs big">
    <?php foreach ($medicos as $m): ?>
    <div class="card doc-card">
        <?= avatar($m, 'xl') ?>
        <h2><?= e($m['nombre']) ?></h2>
        <span class="badge b-paciente"><?= e($m['especialidad']) ?></span>
        <?php if (!empty($m['titulo'])): ?><strong class="cargo"><?= e($m['titulo']) ?></strong><?php endif; ?>
        <?php if (!empty($m['formacion'])): ?><span class="formacion">🎓 <?= e($m['formacion']) ?></span><?php endif; ?>
        <p class="muted"><?= e($m['descripcion']) ?></p>
        <ul class="contacto">
            <?php if (!empty($m['correo'])): ?><li>✉ <a href="mailto:<?= e($m['correo']) ?>"><?= e($m['correo']) ?></a></li><?php endif; ?>
            <?php if (!empty($m['telefono'])): ?><li>☎ <?= e($m['telefono']) ?></li><?php endif; ?>
        </ul>
        <a class="btn sm" href="<?= url('nueva_cita.php?medico=' . $m['id']) ?>">Agendar con este médico</a>
    </div>
    <?php endforeach; ?>
</div>
<?php pagina_fin();

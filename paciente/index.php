<?php
// paciente/index.php — Panel del paciente: sus citas y su próxima cita.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$u = requiere_login('paciente');

// Cancelar una cita propia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar'])) {
    csrf_check();
    $st = $pdo->prepare("UPDATE citas SET estado='Cancelada' WHERE id=? AND usuario_id=? AND estado IN ('Registrada','Confirmada')");
    $st->execute([(int)$_POST['cancelar'], $u['id']]);
    flash($st->rowCount() ? 'ok' : 'error', $st->rowCount() ? 'La cita fue cancelada.' : 'No se pudo cancelar esa cita.');
    redirect('paciente/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_tel'])) {
    csrf_check();
    $tel = trim($_POST['telefono'] ?? '');
    if ($tel !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $tel)) flash('error', 'El teléfono no es válido (solo números, de 7 a 20 caracteres).');
    else { $pdo->prepare("UPDATE usuarios SET telefono=? WHERE id=?")->execute([$tel, $u['id']]); flash('ok', $tel ? 'Teléfono guardado. Lo usaremos para recordarle sus citas.' : 'Teléfono eliminado.'); }
    redirect('paciente/index.php');
}
$miTel = $pdo->prepare("SELECT telefono FROM usuarios WHERE id=?"); $miTel->execute([$u['id']]); $miTel = (string)$miTel->fetchColumn();

$st = $pdo->prepare("SELECT c.*, m.nombre AS medico, m.foto FROM citas c LEFT JOIN medicos m ON m.id=c.medico_id WHERE c.usuario_id=? ORDER BY c.fecha DESC, c.hora DESC");
$st->execute([$u['id']]);
$citas = $st->fetchAll();

$hoy = date('Y-m-d');
$proxima = null; $activas = 0; $atendidas = 0;
foreach (array_reverse($citas) as $c) {
    if (in_array($c['estado'], ['Registrada', 'Confirmada'])) {
        $activas++;
        if ($c['fecha'] >= $hoy && !$proxima) $proxima = $c;
    }
    if ($c['estado'] === 'Atendida') $atendidas++;
}

pagina_inicio('Mis citas', 'mis');
$dias = $proxima ? (int)((strtotime($proxima['fecha']) - strtotime($hoy)) / 86400) : 99;
if ($proxima && $dias <= 2): ?>
<div class="alert a-info">🔔 <strong>Recordatorio:</strong> tiene una cita <?= $dias === 0 ? 'HOY' : ($dias === 1 ? 'MAÑANA' : 'en 2 días (' . e(fecha_larga($proxima['fecha'])) . ')') ?> a las <?= e($proxima['hora']) ?>
    <?= $proxima['medico'] ? 'con ' . e($proxima['medico']) : '(' . e($proxima['especialidad']) . ')' ?>. Para confirmar o cambiarla llame al <strong><?= e(TEL_CONTACTO) ?></strong>.</div>
<?php endif; ?>
<div class="page-head">
    <h1>Hola, <?= e(explode(' ', $u['nombre'])[0]) ?> 👋</h1>
    <p class="muted">Aquí puede revisar y administrar sus citas médicas.</p>
</div>

<div class="stats">
    <div class="stat"><span><?= count($citas) ?></span>Citas en total</div>
    <div class="stat"><span><?= $activas ?></span>Citas activas</div>
    <div class="stat"><span><?= $atendidas ?></span>Atendidas</div>
    <div class="stat next">
        <?php if ($proxima): ?>
            <span><?= e(fecha_larga($proxima['fecha'])) ?></span>
            Próxima: <?= e($proxima['especialidad']) ?> · <?= e($proxima['hora']) ?>
        <?php else: ?>
            <span>—</span>Sin citas próximas
        <?php endif; ?>
    </div>
</div>

<div class="card rec-card">
    <div><strong>🔔 Recordatorios de citas</strong>
    <p class="muted small" style="margin:2px 0 0">Central de citas: <a href="tel:<?= e(preg_replace('/\s+/', '', TEL_CONTACTO)) ?>"><strong><?= e(TEL_CONTACTO) ?></strong></a>
    <?= $miTel ? ' · Sus recordatorios se enviarán al <strong>' . e($miTel) . '</strong>' : ' · Guarde su teléfono para recibir recordatorios' ?></p></div>
    <form method="POST" class="tel-form"><?= csrf_field() ?>
        <input type="text" name="telefono" placeholder="Su teléfono" value="<?= e($miTel) ?>" maxlength="20">
        <button class="btn sm" name="guardar_tel" value="1">Guardar</button></form>
</div>

<div class="card">
    <div class="card-head"><h2>Mis citas</h2><a class="btn sm" href="<?= url('nueva_cita.php') ?>">+ Agendar cita</a></div>
    <?php if (!$citas): ?>
        <p class="empty">Aún no tiene citas. Pulse «Agendar cita» para registrar la primera.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table>
        <thead><tr><th>Médico</th><th>Fecha</th><th>Hora</th><th>Motivo</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($citas as $c): ?>
            <tr>
                <td><?php if ($c['medico']): ?><div class="doc-line"><?= avatar(['nombre' => $c['medico'], 'foto' => $c['foto']], 'sm') ?><div><strong><?= e($c['medico']) ?></strong><br><small class="muted"><?= e($c['especialidad']) ?></small></div></div>
                    <?php else: ?><?= e($c['especialidad']) ?><?php endif; ?></td>
                <td><?= e(fecha_larga($c['fecha'])) ?></td>
                <td><?= e($c['hora']) ?></td>
                <td class="muted"><?= e($c['motivo'] ?: '—') ?></td>
                <td><?= badge_estado($c['estado']) ?></td>
                <td>
                    <?php if (in_array($c['estado'], ['Registrada', 'Confirmada'])): ?>
                        <a class="btn sm ghost" href="<?= url('reagendar.php?id=' . $c['id']) ?>">Reprogramar</a>
                        <form method="POST" class="inline" onsubmit="return confirm('¿Cancelar esta cita?');">
                            <?= csrf_field() ?>
                            <button class="btn sm danger" name="cancelar" value="<?= $c['id'] ?>">Cancelar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php pagina_fin();

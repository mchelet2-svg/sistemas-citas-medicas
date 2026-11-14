<?php
// admin/citas.php — Gestión de todas las citas: filtrar, cambiar estado y eliminar.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requiere_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (isset($_POST['estado']) && in_array($_POST['estado'], ESTADOS, true)) {
        $pdo->prepare("UPDATE citas SET estado=? WHERE id=?")->execute([$_POST['estado'], $id]);
        flash('ok', "Cita #$id marcada como «{$_POST['estado']}».");
    } elseif (isset($_POST['eliminar'])) {
        $pdo->prepare("DELETE FROM citas WHERE id=?")->execute([$id]);
        flash('ok', "Cita #$id eliminada.");
    }
    redirect('admin/citas.php' . (!empty($_POST['qs']) ? '?' . $_POST['qs'] : ''));
}

// Filtros
$f = ['q' => trim($_GET['q'] ?? ''), 'estado' => $_GET['estado'] ?? '', 'esp' => $_GET['esp'] ?? '', 'fecha' => $_GET['fecha'] ?? ''];
$where = []; $par = [];
if ($f['q'] !== '')      { $where[] = 'c.paciente_nombre LIKE ?'; $par[] = '%' . $f['q'] . '%'; }
if (in_array($f['estado'], ESTADOS, true))          { $where[] = 'c.estado=?';     $par[] = $f['estado']; }
if (in_array($f['esp'], ESPECIALIDADES, true))      { $where[] = 'c.especialidad=?'; $par[] = $f['esp']; }
if ($f['fecha'] !== '')  { $where[] = 'c.fecha=?'; $par[] = $f['fecha']; }

$st = $pdo->prepare("SELECT c.*, m.nombre AS medico, m.foto FROM citas c LEFT JOIN medicos m ON m.id=c.medico_id" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY c.fecha DESC, c.hora DESC");
$st->execute($par);
$citas = $st->fetchAll();
$qs = $_SERVER['QUERY_STRING'] ?? '';

pagina_inicio('Gestión de citas', 'citas');
?>
<div class="page-head row">
    <div><h1>Gestión de citas</h1><p class="muted"><?= count($citas) ?> cita(s) encontradas.</p></div>
    <a class="btn" href="<?= url('nueva_cita.php') ?>">+ Nueva cita</a>
</div>

<form class="filters card" method="GET">
    <input type="search" name="q" placeholder="Buscar paciente…" value="<?= e($f['q']) ?>">
    <select name="estado"><option value="">Todos los estados</option>
        <?php foreach (ESTADOS as $s): ?><option <?= $s === $f['estado'] ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
    <select name="esp"><option value="">Todas las especialidades</option>
        <?php foreach (ESPECIALIDADES as $s): ?><option <?= $s === $f['esp'] ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
    <input type="date" name="fecha" value="<?= e($f['fecha']) ?>">
    <button class="btn sm" type="submit">Filtrar</button>
    <a class="btn sm ghost" href="<?= url('admin/citas.php') ?>">Limpiar</a>
</form>

<div class="card">
<?php if (!$citas): ?><p class="empty">No hay citas con esos filtros.</p><?php else: ?>
<div class="table-wrap"><table>
    <thead><tr><th>#</th><th>Paciente</th><th>Médico</th><th>Fecha y hora</th><th>Motivo</th><th>Estado</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($citas as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= e($c['paciente_nombre']) ?></td>
            <td><?php if ($c['medico']): ?><div class="doc-line"><?= avatar(['nombre' => $c['medico'], 'foto' => $c['foto']], 'sm') ?><div><strong><?= e($c['medico']) ?></strong><br><small class="muted"><?= e($c['especialidad']) ?></small></div></div>
                    <?php else: ?><?= e($c['especialidad']) ?><?php endif; ?></td>
            <td><?= e(fecha_larga($c['fecha'])) ?> · <?= e($c['hora']) ?></td>
            <td class="muted"><?= e($c['motivo'] ?: '—') ?></td>
            <td><?= badge_estado($c['estado']) ?></td>
            <td class="actions">
                <form method="POST" class="inline">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="qs" value="<?= e($qs) ?>">
                    <select name="estado" onchange="this.form.submit()" aria-label="Cambiar estado">
                        <?php foreach (ESTADOS as $s): ?><option <?= $s === ($c['estado'] ?: 'Registrada') ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                    </select>
                </form>
                <?php if (in_array($c['estado'], ['Registrada', 'Confirmada']) && $c['medico']): ?><a class="btn sm ghost" href="<?= url('reagendar.php?id=' . $c['id']) ?>">Reprogramar</a><?php endif; ?>
                <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar definitivamente esta cita?');">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><input type="hidden" name="qs" value="<?= e($qs) ?>">
                    <button class="btn sm danger" name="eliminar" value="1">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
<?php endif; ?>
</div>
<?php pagina_fin();

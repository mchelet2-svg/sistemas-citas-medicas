<?php
// reagendar.php — Reprogramar una cita (cambiar fecha y hora con el mismo médico).
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$u = requiere_login();
$esAdmin = $u['rol'] === 'admin';

$st = $pdo->prepare("SELECT c.*, m.nombre AS medico, m.foto, m.id AS mid FROM citas c LEFT JOIN medicos m ON m.id=c.medico_id WHERE c.id=?");
$st->execute([(int)($_GET['id'] ?? $_POST['id'] ?? 0)]);
$c = $st->fetch();
$volver = $esAdmin ? 'admin/citas.php' : 'paciente/index.php';

if (!$c || (!$esAdmin && (int)$c['usuario_id'] !== (int)$u['id'])) { flash('error', 'Cita no encontrada.'); redirect($volver); }
if (!in_array($c['estado'], ['Registrada', 'Confirmada'])) { flash('error', 'Solo se pueden reprogramar citas registradas o confirmadas.'); redirect($volver); }
if (!$c['medico_id']) { flash('error', 'Esta cita antigua no tiene médico asignado; cancélela y agende una nueva.'); redirect($volver); }

$errores = [];
$v = ['fecha' => $c['fecha'], 'hora' => $c['hora']];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v['fecha'] = trim($_POST['fecha'] ?? ''); $v['hora'] = trim($_POST['hora'] ?? '');
    $errores = validar_horario($pdo, (int)$c['medico_id'], $v['fecha'], $v['hora'], (int)$c['id']);
    if (!$errores) {
        $pdo->prepare("UPDATE citas SET fecha=?, hora=?, estado='Registrada' WHERE id=?")->execute([$v['fecha'], $v['hora'], $c['id']]);
        flash('ok', 'Cita reprogramada para el ' . fecha_larga($v['fecha']) . ' a las ' . $v['hora'] . '.');
        redirect($volver);
    }
}
pagina_inicio('Reprogramar cita', $esAdmin ? 'citas' : 'mis');
?>
<div class="page-head"><h1>Reprogramar cita</h1><p class="muted">Cambie la fecha u hora. Al reprogramar, la cita vuelve al estado «Registrada».</p></div>
<div class="card narrow">
    <div class="doc-line"><?= avatar(['nombre' => $c['medico'], 'foto' => $c['foto']], 'md') ?>
        <div><strong><?= e($c['medico']) ?></strong><br><span class="muted"><?= e($c['especialidad']) ?> · Paciente: <?= e($c['paciente_nombre']) ?></span></div></div>
    <p class="muted">Cita actual: <strong><?= e(fecha_larga($c['fecha'])) ?> · <?= e($c['hora']) ?></strong></p>
    <?php foreach ($errores as $er): ?><div class="alert a-error"><?= e($er) ?></div><?php endforeach; ?>
    <form method="POST">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
        <div class="grid2">
            <label>Nueva fecha<input type="date" name="fecha" id="fecha" min="<?= date('Y-m-d') ?>" value="<?= e($v['fecha']) ?>" required></label>
            <label>Nueva hora
                <select name="hora" id="hora" required>
                    <?php foreach (horarios() as $h): ?><option <?= $h === $v['hora'] ? 'selected' : '' ?>><?= $h ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <button class="btn" type="submit">Guardar cambios</button>
    </form>
    <p class="center"><a href="<?= url($volver) ?>">← Volver</a></p>
</div>
<script>
const BASE = <?= json_encode(url('')) ?>, MED = <?= (int)$c['medico_id'] ?>, ID = <?= (int)$c['id'] ?>;
const fecha = document.getElementById('fecha'), hora = document.getElementById('hora');
async function act() {
    [...hora.options].forEach(o => { o.disabled = false; o.textContent = o.value; });
    if (!fecha.value) return;
    try {
        const j = await (await fetch(BASE + '/horas.php?medico=' + MED + '&fecha=' + fecha.value + '&excluir=' + ID)).json();
        [...hora.options].forEach(o => { if (j.ocupadas.includes(o.value)) { o.disabled = true; o.textContent = o.value + ' (ocupada)'; } });
    } catch (e) {}
}
fecha.addEventListener('change', act); act();
</script>
<?php pagina_fin();

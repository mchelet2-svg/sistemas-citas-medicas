<?php
// nueva_cita.php — Agendar cita eligiendo médico (paciente: para sí mismo; admin: elige paciente).
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/layout.php';
$u = requiere_login();
$esAdmin = $u['rol'] === 'admin';

$medicos   = $pdo->query("SELECT * FROM medicos WHERE activo=1 ORDER BY especialidad, nombre")->fetchAll();
$pacientes = $esAdmin ? $pdo->query("SELECT id, nombre, usuario FROM usuarios WHERE rol='paciente' ORDER BY nombre")->fetchAll() : [];
$v = ['paciente' => '', 'medico' => (string)(int)($_GET['medico'] ?? 0), 'fecha' => '', 'hora' => '', 'motivo' => ''];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($v as $k => $_) $v[$k] = trim($_POST[$k] ?? '');

    $pid = $u['id']; $pnombre = $u['nombre'];
    if ($esAdmin) {
        $st = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE id=? AND rol='paciente'");
        $st->execute([(int)$v['paciente']]);
        $p = $st->fetch();
        if (!$p) $errores[] = 'Seleccione un paciente.'; else { $pid = $p['id']; $pnombre = $p['nombre']; }
    }

    $st = $pdo->prepare("SELECT * FROM medicos WHERE id=? AND activo=1");
    $st->execute([(int)$v['medico']]);
    $med = $st->fetch();
    if (!$med) $errores[] = 'Seleccione un médico.';
    if (mb_strlen($v['motivo']) > 200) $errores[] = 'El motivo no puede superar 200 caracteres.';
    if ($med) $errores = array_merge($errores, validar_horario($pdo, $med['id'], $v['fecha'], $v['hora']));

    if (!$errores) {
        $pdo->prepare("INSERT INTO citas (usuario_id, medico_id, paciente_nombre, especialidad, fecha, hora, motivo, estado, creado)
                       VALUES (?,?,?,?,?,?,?,'Registrada',?)")
            ->execute([$pid, $med['id'], $pnombre, $med['especialidad'], $v['fecha'], $v['hora'], $v['motivo'], date('Y-m-d H:i:s')]);
        flash('ok', 'Cita registrada con ' . $med['nombre'] . ' el ' . fecha_larga($v['fecha']) . ' a las ' . $v['hora'] . '.');
        redirect($esAdmin ? 'admin/citas.php' : 'paciente/index.php');
    }
}

$espList = array_values(array_unique(array_column($medicos, 'especialidad')));
pagina_inicio('Agendar cita', 'nueva');
?>
<div class="page-head"><h1>Agendar cita médica</h1><p class="muted">1) Elija el médico · 2) Escoja fecha y hora · 3) Confirme. Los horarios son cada 30 minutos, de lunes a sábado.</p></div>
<form method="POST" id="frm">
    <?= csrf_field() ?>
    <?php foreach ($errores as $er): ?><div class="alert a-error"><?= e($er) ?></div><?php endforeach; ?>

    <?php if ($esAdmin): ?>
    <div class="card">
        <label>Paciente
            <select name="paciente" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($pacientes as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (string)$p['id'] === $v['paciente'] ? 'selected' : '' ?>><?= e($p['nombre']) ?> (<?= e($p['usuario']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head"><h2>1. Elija su médico</h2></div>
        <div class="tabs" id="tabs">
            <button type="button" class="tab on" data-esp="">Todos</button>
            <?php foreach ($espList as $es): ?><button type="button" class="tab" data-esp="<?= e($es) ?>"><?= e($es) ?></button><?php endforeach; ?>
        </div>
        <div class="docs">
            <?php foreach ($medicos as $m): ?>
            <label class="doc" data-esp="<?= e($m['especialidad']) ?>">
                <input type="radio" name="medico" value="<?= $m['id'] ?>" <?= (string)$m['id'] === $v['medico'] ? 'checked' : '' ?> required>
                <div class="doc-in">
                    <?= avatar($m, 'lg') ?>
                    <strong><?= e($m['nombre']) ?></strong>
                    <small><?= e($m['especialidad']) ?></small>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card narrow">
        <div class="card-head"><h2>2. Fecha y hora</h2></div>
        <div class="grid2">
            <label>Fecha<input type="date" name="fecha" id="fecha" min="<?= date('Y-m-d') ?>" value="<?= e($v['fecha']) ?>" required></label>
            <label>Hora
                <select name="hora" id="hora" required>
                    <option value="">— Hora —</option>
                    <?php foreach (horarios() as $h): ?><option <?= $h === $v['hora'] ? 'selected' : '' ?>><?= $h ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <p class="muted small" id="hint">Las horas ocupadas aparecen deshabilitadas.</p>
        <label>Motivo de la consulta (opcional)
            <textarea name="motivo" rows="3" maxlength="200" placeholder="Ej.: control, dolor de cabeza, revisión dental…"><?= e($v['motivo']) ?></textarea>
        </label>
        <button class="btn" type="submit">Confirmar cita</button>
    </div>
</form>

<script>
const BASE = <?= json_encode(url('')) ?>;
const fecha = document.getElementById('fecha'), hora = document.getElementById('hora');
// Filtro por especialidad
document.querySelectorAll('.tab').forEach(t => t.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(x => x.classList.toggle('on', x === t));
    document.querySelectorAll('.doc').forEach(d => d.style.display = (!t.dataset.esp || d.dataset.esp === t.dataset.esp) ? '' : 'none');
}));
// Horas ocupadas según médico y fecha
async function actualizarHoras() {
    const med = document.querySelector('input[name=medico]:checked');
    [...hora.options].forEach(o => { if (!o.value) return; o.disabled = false; o.textContent = o.value; });
    if (!med || !fecha.value) return;
    try {
        const r = await fetch(BASE + '/horas.php?medico=' + med.value + '&fecha=' + fecha.value);
        const j = await r.json();
        [...hora.options].forEach(o => { if (o.value && j.ocupadas.includes(o.value)) { o.disabled = true; o.textContent = o.value + ' (ocupada)'; } });
        if (hora.selectedOptions[0] && hora.selectedOptions[0].disabled) hora.value = '';
    } catch (e) {}
}
document.querySelectorAll('input[name=medico]').forEach(r => r.addEventListener('change', actualizarHoras));
fecha.addEventListener('change', actualizarHoras);
actualizarHoras();
</script>
<?php pagina_fin();

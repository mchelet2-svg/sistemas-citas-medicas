<?php
// admin/index.php — Panel de administración: indicadores y próximas citas.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requiere_login('admin');

$hoy = date('Y-m-d');
$q = fn(string $sql, array $p = []) => (function () use ($pdo, $sql, $p) { $s = $pdo->prepare($sql); $s->execute($p); return $s; })();

$total     = (int)$q("SELECT COUNT(*) FROM citas")->fetchColumn();
$citasHoy  = (int)$q("SELECT COUNT(*) FROM citas WHERE fecha=? AND estado<>'Cancelada'", [$hoy])->fetchColumn();
$pend      = (int)$q("SELECT COUNT(*) FROM citas WHERE estado='Registrada'")->fetchColumn();
$pacientes = (int)$q("SELECT COUNT(*) FROM usuarios WHERE rol='paciente'")->fetchColumn();

$proximas = $q("SELECT * FROM citas WHERE fecha>=? AND estado IN ('Registrada','Confirmada') ORDER BY fecha, hora LIMIT 8", [$hoy])->fetchAll();
$porEsp   = $q("SELECT especialidad, COUNT(*) n FROM citas WHERE estado<>'Cancelada' GROUP BY especialidad ORDER BY n DESC")->fetchAll();
$maxEsp   = max(array_column($porEsp, 'n') ?: [1]);

pagina_inicio('Panel de administración', 'panel');
?>
<div class="page-head"><h1>Panel de administración</h1><p class="muted">Resumen general del sistema.</p></div>

<div class="stats">
    <div class="stat"><span><?= $total ?></span>Citas registradas</div>
    <div class="stat"><span><?= $citasHoy ?></span>Citas de hoy</div>
    <div class="stat warn"><span><?= $pend ?></span>Por confirmar</div>
    <div class="stat"><span><?= $pacientes ?></span>Pacientes</div>
</div>

<div class="cols">
    <div class="card">
        <div class="card-head"><h2>Próximas citas</h2><a class="btn sm ghost" href="<?= url('admin/citas.php') ?>">Ver todas</a></div>
        <?php if (!$proximas): ?><p class="empty">No hay citas próximas.</p><?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Paciente</th><th>Especialidad</th><th>Fecha</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($proximas as $c): ?>
                <tr>
                    <td><?= e($c['paciente_nombre']) ?></td>
                    <td><?= e($c['especialidad']) ?></td>
                    <td><?= e(fecha_larga($c['fecha'])) ?> · <?= e($c['hora']) ?></td>
                    <td><?= badge_estado($c['estado']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head"><h2>Citas por especialidad</h2></div>
        <?php if (!$porEsp): ?><p class="empty">Sin datos todavía.</p><?php endif; ?>
        <?php foreach ($porEsp as $r): ?>
            <div class="bar-row">
                <div class="bar-label"><span><?= e($r['especialidad']) ?></span><strong><?= $r['n'] ?></strong></div>
                <div class="bar"><i style="width:<?= round($r['n'] / $maxEsp * 100) ?>%"></i></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php pagina_fin();

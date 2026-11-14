<?php
// admin/medicos.php — Gestión de médicos: crear, editar, subir foto, activar/desactivar.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
requiere_login('admin');
$errores = [];
$editar = null;

// Guarda la foto subida (jpg/png/webp, máx. 2 MB) y devuelve el nombre de archivo o null
function guardar_foto(array $f, array &$errores): ?string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) { $errores[] = 'No se pudo subir la foto.'; return null; }
    if ($f['size'] > 2 * 1024 * 1024) { $errores[] = 'La foto no puede superar 2 MB.'; return null; }
    $info = @getimagesize($f['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'][$info[2] ?? 0] ?? null;
    if (!$ext) { $errores[] = 'La foto debe ser JPG, PNG o WEBP.'; return null; }
    $nombre = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/../uploads/' . $nombre)) { $errores[] = 'No se pudo guardar la foto.'; return null; }
    return $nombre;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($accion === 'guardar') {
        $nombre = trim($_POST['nombre'] ?? ''); $esp = trim($_POST['especialidad'] ?? '');
        $desc = trim($_POST['descripcion'] ?? '');
        $titulo = trim($_POST['titulo'] ?? ''); $forma = trim($_POST['formacion'] ?? '');
        $correo = trim($_POST['correo'] ?? ''); $tel = trim($_POST['telefono'] ?? '');
        if (mb_strlen($nombre) < 5) $errores[] = 'Escriba el nombre del médico (ej.: Dra. Ana Pérez).';
        if (!in_array($esp, ESPECIALIDADES, true)) $errores[] = 'Seleccione una especialidad.';
        if (mb_strlen($desc) > 300) $errores[] = 'La biografía no puede superar 300 caracteres.';
        if (mb_strlen($titulo) > 80 || mb_strlen($forma) > 150) $errores[] = 'El título o la formación son demasiado largos.';
        if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errores[] = 'El correo no es válido.';
        if ($tel !== '' && !preg_match('/^[0-9+\s()-]{7,20}$/', $tel)) $errores[] = 'El teléfono no es válido (solo números, de 7 a 20 caracteres).';
        $foto = $errores ? null : guardar_foto($_FILES['foto'] ?? [], $errores);
        if (!$errores) {
            if ($id) {
                $pdo->prepare("UPDATE medicos SET nombre=?, especialidad=?, descripcion=?, titulo=?, formacion=?, correo=?, telefono=? WHERE id=?")
                    ->execute([$nombre, $esp, $desc, $titulo, $forma, $correo, $tel, $id]);
                if ($foto) $pdo->prepare("UPDATE medicos SET foto=? WHERE id=?")->execute([$foto, $id]);
                flash('ok', 'Médico actualizado.');
            } else {
                $pdo->prepare("INSERT INTO medicos (nombre, especialidad, descripcion, titulo, formacion, correo, telefono, foto, creado) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$nombre, $esp, $desc, $titulo, $forma, $correo, $tel, $foto, date('Y-m-d H:i:s')]);
                flash('ok', 'Médico agregado.');
            }
            redirect('admin/medicos.php');
        }
        $editar = ['id' => $id, 'nombre' => $nombre, 'especialidad' => $esp, 'descripcion' => $desc, 'titulo' => $titulo,
                   'formacion' => $forma, 'correo' => $correo, 'telefono' => $tel, 'foto' => null];
    } elseif ($accion === 'estado') {
        $pdo->prepare("UPDATE medicos SET activo = 1 - activo WHERE id=?")->execute([$id]);
        flash('ok', 'Estado del médico actualizado.');
        redirect('admin/medicos.php');
    } elseif ($accion === 'quitar_foto') {
        $pdo->prepare("UPDATE medicos SET foto=NULL WHERE id=?")->execute([$id]);
        flash('ok', 'Foto eliminada.');
        redirect('admin/medicos.php?editar=' . $id);
    }
}

if (!$editar && isset($_GET['editar'])) {
    $st = $pdo->prepare("SELECT * FROM medicos WHERE id=?"); $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch() ?: null;
}
$medicos = $pdo->query("SELECT m.*, (SELECT COUNT(*) FROM citas c WHERE c.medico_id=m.id) AS ncitas FROM medicos m ORDER BY activo DESC, especialidad, nombre")->fetchAll();

pagina_inicio('Médicos', 'medicos');
?>
<div class="page-head"><h1>Médicos</h1><p class="muted">Agregue médicos, suba su fotografía y controle cuáles reciben citas.</p></div>
<div class="cols">
    <div class="card">
        <div class="card-head"><h2>Equipo médico (<?= count($medicos) ?>)</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Médico</th><th>Especialidad</th><th>Citas</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($medicos as $m): ?>
                <tr style="<?= $m['activo'] ? '' : 'opacity:.55' ?>">
                    <td><div class="doc-line"><?= avatar($m, 'sm') ?><strong><?= e($m['nombre']) ?></strong></div></td>
                    <td><?= e($m['especialidad']) ?></td>
                    <td><?= $m['ncitas'] ?></td>
                    <td><span class="badge <?= $m['activo'] ? 'b-atendida' : 'b-cancelada' ?>"><?= $m['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                    <td class="actions">
                        <a class="btn sm ghost" href="<?= url('admin/medicos.php?editar=' . $m['id']) ?>">Editar</a>
                        <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button class="btn sm <?= $m['activo'] ? 'danger' : 'ghost' ?>" name="accion" value="estado"><?= $m['activo'] ? 'Desactivar' : 'Activar' ?></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="card-head"><h2><?= $editar && $editar['id'] ? 'Editar médico' : 'Nuevo médico' ?></h2>
            <?php if ($editar): ?><a class="btn sm ghost" href="<?= url('admin/medicos.php') ?>">Cancelar</a><?php endif; ?></div>
        <?php foreach ($errores as $er): ?><div class="alert a-error"><?= e($er) ?></div><?php endforeach; ?>
        <?php if ($editar && $editar['id']): ?>
            <div class="doc-line" style="margin-bottom:12px"><?= avatar($editar, 'md') ?>
            <?php if (!empty($editar['foto'])): ?>
                <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $editar['id'] ?>">
                    <button class="btn sm danger" name="accion" value="quitar_foto">Quitar foto</button></form>
            <?php endif; ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
            <label>Nombre (con Dr./Dra.)<input type="text" name="nombre" value="<?= e($editar['nombre'] ?? '') ?>" required></label>
            <label>Especialidad
                <select name="especialidad"><?php foreach (ESPECIALIDADES as $es): ?>
                    <option <?= ($editar['especialidad'] ?? '') === $es ? 'selected' : '' ?>><?= e($es) ?></option><?php endforeach; ?></select>
            </label>
            <label>Cargo o título profesional<input type="text" name="titulo" maxlength="80" placeholder="Ej.: Pediatra" value="<?= e($editar['titulo'] ?? '') ?>"></label>
            <label>Formación (título, posgrado o doctorado)<input type="text" name="formacion" maxlength="150" placeholder="Ej.: Especialidad en Pediatría – Universidad de Guayaquil" value="<?= e($editar['formacion'] ?? '') ?>"></label>
            <div class="grid2">
                <label>Correo<input type="email" name="correo" placeholder="nombre@correo.com" value="<?= e($editar['correo'] ?? '') ?>"></label>
                <label>Teléfono<input type="text" name="telefono" placeholder="0990000000" value="<?= e($editar['telefono'] ?? '') ?>"></label>
            </div>
            <label>Biografía breve<textarea name="descripcion" rows="3" maxlength="300"><?= e($editar['descripcion'] ?? '') ?></textarea></label>
            <label>Fotografía (JPG, PNG o WEBP, máx. 2 MB)<input type="file" name="foto" accept="image/jpeg,image/png,image/webp"></label>
            <button class="btn" type="submit"><?= $editar && $editar['id'] ? 'Guardar cambios' : 'Agregar médico' ?></button>
        </form>
    </div>
</div>
<?php pagina_fin();

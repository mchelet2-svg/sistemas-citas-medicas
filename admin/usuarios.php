<?php
// admin/usuarios.php — Gestión de usuarios: crear, cambiar rol, restablecer clave y eliminar.
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$yo = requiere_login('admin');
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $admins = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol='admin'")->fetchColumn();

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? ''); $user = strtolower(trim($_POST['usuario'] ?? ''));
        $pass = $_POST['pass'] ?? ''; $rol = $_POST['rol'] ?? 'paciente';
        if (mb_strlen($nombre) < 3) $errores[] = 'Escriba el nombre completo.';
        if (!preg_match('/^[a-z0-9_.]{4,20}$/', $user)) $errores[] = 'Usuario: 4 a 20 caracteres (letras, números, punto o guion bajo).';
        if (strlen($pass) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        if (!in_array($rol, ROLES, true)) $errores[] = 'Rol no válido.';
        if (!$errores) {
            $st = $pdo->prepare("SELECT 1 FROM usuarios WHERE usuario=?"); $st->execute([$user]);
            if ($st->fetch()) $errores[] = 'Ese usuario ya existe.';
        }
        if (!$errores) {
            $pdo->prepare("INSERT INTO usuarios (nombre, usuario, password, rol, creado) VALUES (?,?,?,?,?)")
                ->execute([$nombre, $user, password_hash($pass, PASSWORD_DEFAULT), $rol, date('Y-m-d H:i:s')]);
            flash('ok', "Usuario «{$user}» creado.");
            redirect('admin/usuarios.php');
        }
    } elseif ($accion === 'rol') {
        $st = $pdo->prepare("SELECT rol FROM usuarios WHERE id=?"); $st->execute([$id]);
        $r = $st->fetchColumn();
        if ($r === false) flash('error', 'Usuario no encontrado.');
        elseif ($id === $yo['id']) flash('error', 'No puede cambiar su propio rol.');
        else {
            $nuevo = $r === 'admin' ? 'paciente' : 'admin';
            $pdo->prepare("UPDATE usuarios SET rol=? WHERE id=?")->execute([$nuevo, $id]);
            flash('ok', "Rol actualizado a $nuevo.");
        }
        redirect('admin/usuarios.php');
    } elseif ($accion === 'clave') {
        $n = $_POST['nueva'] ?? '';
        if (strlen($n) < 6) flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
        else { $pdo->prepare("UPDATE usuarios SET password=? WHERE id=?")->execute([password_hash($n, PASSWORD_DEFAULT), $id]); flash('ok', 'Contraseña restablecida.'); }
        redirect('admin/usuarios.php');
    } elseif ($accion === 'eliminar') {
        if ($id === $yo['id']) flash('error', 'No puede eliminar su propia cuenta.');
        else {
            $pdo->prepare("DELETE FROM usuarios WHERE id=?")->execute([$id]);
            flash('ok', 'Usuario eliminado (sus citas se conservan en el historial).');
        }
        redirect('admin/usuarios.php');
    }
}

$usuarios = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM citas c WHERE c.usuario_id=u.id) AS ncitas FROM usuarios u ORDER BY rol, nombre")->fetchAll();

pagina_inicio('Usuarios', 'usuarios');
?>
<div class="page-head"><h1>Usuarios</h1><p class="muted">Administre las cuentas de administradores y pacientes.</p></div>

<div class="cols">
    <div class="card">
        <div class="card-head"><h2>Listado (<?= count($usuarios) ?>)</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Citas</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $x): ?>
                <tr>
                    <td><?= e($x['nombre']) ?></td>
                    <td><code><?= e($x['usuario']) ?></code></td>
                    <td><span class="badge <?= $x['rol'] === 'admin' ? 'b-admin' : 'b-paciente' ?>"><?= $x['rol'] === 'admin' ? 'Administrador' : 'Paciente' ?></span></td>
                    <td><?= $x['ncitas'] ?></td>
                    <td class="actions">
                        <?php if ($x['id'] === $yo['id']): ?><span class="muted">Es usted</span><?php else: ?>
                        <form method="POST" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>">
                            <button class="btn sm ghost" name="accion" value="rol">Cambiar rol</button></form>
                        <form method="POST" class="inline" onsubmit="var p=prompt('Nueva contraseña (mínimo 6 caracteres):'); if(!p) return false; this.nueva.value=p; return true;">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>"><input type="hidden" name="nueva">
                            <button class="btn sm ghost" name="accion" value="clave">Restablecer clave</button></form>
                        <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este usuario?');">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $x['id'] ?>">
                            <button class="btn sm danger" name="accion" value="eliminar">Eliminar</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Nuevo usuario</h2></div>
        <?php foreach ($errores as $er): ?><div class="alert a-error"><?= e($er) ?></div><?php endforeach; ?>
        <form method="POST">
            <?= csrf_field() ?><input type="hidden" name="accion" value="crear">
            <label>Nombre completo<input type="text" name="nombre" required></label>
            <label>Usuario<input type="text" name="usuario" required></label>
            <label>Contraseña<input type="password" name="pass" required></label>
            <label>Rol
                <select name="rol"><option value="paciente">Paciente</option><option value="admin">Administrador</option></select>
            </label>
            <button class="btn" type="submit">Crear usuario</button>
        </form>
    </div>
</div>
<?php pagina_fin();
